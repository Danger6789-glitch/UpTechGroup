<?php
require_once 'config.php';
header('Content-Type: application/json');
if (!isset($_SESSION['user_id']) || $_SESSION['role'] !== 'admin') {
    echo json_encode(['success'=>false,'message'=>'Accès refusé']); exit;
}
$action = $_POST['action'] ?? $_GET['action'] ?? '';

if ($action === 'create_match') {
    $stmt = $pdo->prepare("INSERT INTO matches (home_team,away_team,match_date,match_time,venue) VALUES (?,?,?,?,?)");
    $stmt->execute([$_POST['home_team'],$_POST['away_team'],$_POST['match_date'],$_POST['match_time'],$_POST['venue']]);
    echo json_encode(['success'=>true,'message'=>'Match créé','id'=>$pdo->lastInsertId()]);
}

if ($action === 'update_match') {
    $stmt = $pdo->prepare("UPDATE matches SET match_date=?,match_time=?,venue=?,status=?,home_team=?,away_team=? WHERE id=?");
    $stmt->execute([$_POST['match_date'],$_POST['match_time'],$_POST['venue'],$_POST['status'],$_POST['home_team'],$_POST['away_team'],$_POST['match_id']]);
    echo json_encode(['success'=>true,'message'=>'Match mis à jour']);
}

if ($action === 'delete_match') {
    $pdo->prepare("DELETE FROM matches WHERE id=?")->execute([$_POST['match_id']]);
    $pdo->prepare("DELETE FROM match_stats WHERE match_id=?")->execute([$_POST['match_id']]);
    echo json_encode(['success'=>true,'message'=>'Match supprimé']);
}

if ($action === 'save_score') {
    $stmt = $pdo->prepare("UPDATE matches SET score_home=?,score_away=?,status='finished' WHERE id=?");
    $stmt->execute([$_POST['score_home'],$_POST['score_away'],$_POST['match_id']]);
    echo json_encode(['success'=>true,'message'=>'Score enregistré']);
}

if ($action === 'set_live') {
    $pdo->prepare("UPDATE matches SET status='live',score_home=?,score_away=? WHERE id=?")->execute([$_POST['score_home']??0,$_POST['score_away']??0,$_POST['match_id']]);
    echo json_encode(['success'=>true]);
}

if ($action === 'update_player') {
    $stmt = $pdo->prepare("UPDATE users SET level=?,value=?,team=? WHERE id=?");
    $stmt->execute([$_POST['level'],$_POST['value'],$_POST['team'],$_POST['player_id']]);
    echo json_encode(['success'=>true]);
}

if ($action === 'create_manager') {
    $id = uniqid('manager_');
    $pwd = hash('sha256',$_POST['password']);
    $stmt = $pdo->prepare("INSERT INTO users (id,name,email,password,role,team,status) VALUES (?,?,?,?,'manager',?,'active')");
    $stmt->execute([$id,$_POST['name'],$_POST['email'],$pwd,$_POST['team']]);
    $pdo->prepare("UPDATE teams SET manager_id=? WHERE id=?")->execute([$id,$_POST['team']]);
    echo json_encode(['success'=>true,'message'=>'Responsable créé']);
}

if ($action === 'all_users') {
    $stmt = $pdo->query("SELECT u.*,t.name as team_name FROM users u LEFT JOIN teams t ON t.id=u.team ORDER BY u.created_at DESC");
    echo json_encode(['success'=>true,'data'=>$stmt->fetchAll(PDO::FETCH_ASSOC)]);
}

if ($action === 'delete_user') {
    if ($_POST['user_id'] === $_SESSION['user_id']) {
        echo json_encode(['success'=>false,'message'=>'Impossible de supprimer votre propre compte']); exit;
    }
    $pdo->prepare("DELETE FROM users WHERE id=? AND role!='admin'")->execute([$_POST['user_id']]);
    echo json_encode(['success'=>true]);
}

if ($action === 'get_teams') {
    $stmt = $pdo->query("SELECT * FROM teams ORDER BY name ASC");
    echo json_encode(['success'=>true,'data'=>$stmt->fetchAll(PDO::FETCH_ASSOC)]);
}

if ($action === 'get_all_matches') {
    $stmt = $pdo->query("SELECT m.*,ht.name as home_name,at.name as away_name FROM matches m LEFT JOIN teams ht ON ht.id=m.home_team LEFT JOIN teams at ON at.id=m.away_team ORDER BY m.match_date DESC");
    echo json_encode(['success'=>true,'data'=>$stmt->fetchAll(PDO::FETCH_ASSOC)]);
}
