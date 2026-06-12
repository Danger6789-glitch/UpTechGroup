<?php
require_once 'config.php';
header('Content-Type: application/json');
header('Access-Control-Allow-Origin: *');

$action = $_GET['action'] ?? '';

if ($action === 'standings') {
    $stmt = $pdo->query("
        SELECT t.id, t.name, t.color, t.logo, t.whatsapp,
            COUNT(CASE WHEN (m.home_team=t.id AND m.score_home>m.score_away) OR (m.away_team=t.id AND m.score_away>m.score_home) THEN 1 END) as wins,
            COUNT(CASE WHEN (m.home_team=t.id AND m.score_home<m.score_away) OR (m.away_team=t.id AND m.score_away<m.score_home) THEN 1 END) as losses,
            COUNT(CASE WHEN m.status='finished' AND (m.home_team=t.id OR m.away_team=t.id) THEN 1 END) as played,
            COALESCE(AVG(CASE WHEN m.home_team=t.id THEN m.score_home WHEN m.away_team=t.id THEN m.score_away END),0) as ppg
        FROM teams t
        LEFT JOIN matches m ON (m.home_team=t.id OR m.away_team=t.id) AND m.status='finished'
        WHERE t.status='active'
        GROUP BY t.id ORDER BY wins DESC, ppg DESC
    ");
    echo json_encode(['success'=>true,'data'=>$stmt->fetchAll(PDO::FETCH_ASSOC)]);
}

if ($action === 'matches') {
    $status = $_GET['status'] ?? '';
    $team = $_GET['team'] ?? '';
    $base = "SELECT m.*, ht.name as home_name, ht.color as home_color, ht.logo as home_logo, at.name as away_name, at.color as away_color, at.logo as away_logo FROM matches m LEFT JOIN teams ht ON ht.id=m.home_team LEFT JOIN teams at ON at.id=m.away_team";
    if ($status && $team) {
        $stmt = $pdo->prepare("$base WHERE m.status=? AND (m.home_team=? OR m.away_team=?) ORDER BY m.match_date DESC");
        $stmt->execute([$status,$team,$team]);
    } elseif ($status) {
        $stmt = $pdo->prepare("$base WHERE m.status=? ORDER BY m.match_date ASC");
        $stmt->execute([$status]);
    } elseif ($team) {
        $stmt = $pdo->prepare("$base WHERE m.home_team=? OR m.away_team=? ORDER BY m.match_date DESC");
        $stmt->execute([$team,$team]);
    } else {
        $stmt = $pdo->query("$base ORDER BY m.match_date DESC");
    }
    echo json_encode(['success'=>true,'data'=>$stmt->fetchAll(PDO::FETCH_ASSOC)]);
}

if ($action === 'players') {
    $team = $_GET['team'] ?? '';
    if ($team) {
        $stmt = $pdo->prepare("SELECT u.id,u.name,u.team,u.position,u.number,u.level,u.value,u.nationality,u.height,u.photo,t.name as team_name,t.color as team_color FROM users u LEFT JOIN teams t ON t.id=u.team WHERE u.role='player' AND u.status='active' AND u.team=? ORDER BY u.level DESC");
        $stmt->execute([$team]);
    } else {
        $stmt = $pdo->query("SELECT u.id,u.name,u.team,u.position,u.number,u.level,u.value,u.nationality,u.height,u.photo,t.name as team_name,t.color as team_color FROM users u LEFT JOIN teams t ON t.id=u.team WHERE u.role='player' AND u.status='active' ORDER BY u.level DESC");
    }
    echo json_encode(['success'=>true,'data'=>$stmt->fetchAll(PDO::FETCH_ASSOC)]);
}

if ($action === 'player_stats') {
    $player_id = $_GET['player_id'] ?? '';
    $stmt = $pdo->prepare("SELECT COUNT(*) as gp, ROUND(AVG(pts),1) as pts, ROUND(AVG(reb),1) as reb, ROUND(AVG(ast),1) as ast, ROUND(AVG(stl),1) as stl, ROUND(AVG(blk),1) as blk, ROUND(SUM(fg_made)/NULLIF(SUM(fg_attempted),0)*100,1) as fg_pct FROM match_stats WHERE player_id=? AND confirmed=1");
    $stmt->execute([$player_id]);
    echo json_encode(['success'=>true,'data'=>$stmt->fetch(PDO::FETCH_ASSOC)]);
}

if ($action === 'registrations') {
    if (!isset($_SESSION['role'])||!in_array($_SESSION['role'],['admin','manager'])) {
        echo json_encode(['success'=>false,'message'=>'Accès refusé']); exit;
    }
    $team = $_SESSION['team'] ?? '';
    if ($_SESSION['role']==='manager' && $team) {
        $stmt = $pdo->prepare("SELECT * FROM registrations WHERE status='pending' AND team=? ORDER BY submitted_at DESC");
        $stmt->execute([$team]);
    } else {
        $stmt = $pdo->query("SELECT * FROM registrations WHERE status='pending' ORDER BY submitted_at DESC");
    }
    echo json_encode(['success'=>true,'data'=>$stmt->fetchAll(PDO::FETCH_ASSOC)]);
}

if ($action === 'handle_registration' && $_SERVER['REQUEST_METHOD']==='POST') {
    if (!isset($_SESSION['role'])||!in_array($_SESSION['role'],['admin','manager'])) {
        echo json_encode(['success'=>false,'message'=>'Accès refusé']); exit;
    }
    $reg_id = $_POST['reg_id'] ?? '';
    $decision = $_POST['decision'] ?? '';
    $email = $_POST['email'] ?? '';
    $pdo->prepare("UPDATE registrations SET status=? WHERE id=?")->execute([$decision,$reg_id]);
    $status = $decision==='accepted' ? 'active' : 'rejected';
    $pdo->prepare("UPDATE users SET status=? WHERE email=?")->execute([$status,$email]);
    echo json_encode(['success'=>true]);
}

if ($action === 'leaders') {
    $stat = $_GET['stat'] ?? 'pts';
    $allowed = ['pts','reb','ast','stl','blk'];
    if (!in_array($stat,$allowed)) $stat='pts';
    $stmt = $pdo->query("
        SELECT u.id,u.name,u.team,u.position,u.level,u.value,u.height,u.photo,
            t.name as team_name,t.color as team_color,t.logo as team_logo,
            COUNT(ms.id) as gp,
            ROUND(AVG(ms.$stat),1) as stat_value,
            ROUND(SUM(ms.fg_made)/NULLIF(SUM(ms.fg_attempted),0)*100,1) as fg_pct
        FROM users u
        JOIN match_stats ms ON ms.player_id=u.id AND ms.confirmed=1
        LEFT JOIN teams t ON t.id=u.team
        WHERE u.role='player' AND u.status='active'
        GROUP BY u.id ORDER BY stat_value DESC LIMIT 10
    ");
    echo json_encode(['success'=>true,'data'=>$stmt->fetchAll(PDO::FETCH_ASSOC)]);
}

if ($action === 'articles') {
    $cat = $_GET['category'] ?? '';
    $limit = (int)($_GET['limit'] ?? 10);
    if ($cat) {
        $stmt = $pdo->prepare("SELECT a.*,m.filename FROM articles a LEFT JOIN media m ON m.id=a.image_id WHERE a.status='published' AND a.category=? ORDER BY a.created_at DESC LIMIT ?");
        $stmt->execute([$cat,$limit]);
    } else {
        $stmt = $pdo->prepare("SELECT a.*,m.filename FROM articles a LEFT JOIN media m ON m.id=a.image_id WHERE a.status='published' ORDER BY a.created_at DESC LIMIT ?");
        $stmt->execute([$limit]);
    }
    echo json_encode(['success'=>true,'data'=>$stmt->fetchAll(PDO::FETCH_ASSOC)]);
}

if ($action === 'settings') {
    $stmt = $pdo->query("SELECT setting_key, setting_value FROM site_settings");
    $settings = [];
    foreach ($stmt->fetchAll(PDO::FETCH_ASSOC) as $row) $settings[$row['setting_key']] = $row['setting_value'];
    echo json_encode(['success'=>true,'data'=>$settings]);
}
