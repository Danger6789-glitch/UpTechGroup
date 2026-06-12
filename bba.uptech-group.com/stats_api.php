<?php
require_once 'config.php';
header('Content-Type: application/json');
$action = $_POST['action'] ?? $_GET['action'] ?? '';

// CONFIRMER STATS (manager ou admin seulement)
if ($action === 'confirm_stats') {
    if (!isset($_SESSION['role'])||!in_array($_SESSION['role'],['admin','manager'])) {
        echo json_encode(['success'=>false,'message'=>'Acces refuse']); exit;
    }
    $pdo->prepare("UPDATE match_stats SET confirmed=1 WHERE id=?")->execute([$_POST['stat_id']]);
    $pid_stmt = $pdo->prepare("SELECT player_id FROM match_stats WHERE id=?");
    $pid_stmt->execute([$_POST['stat_id']]);
    $pid = $pid_stmt->fetchColumn();
    $avg = $pdo->prepare("SELECT AVG(pts) as avg_pts,AVG(reb) as avg_reb,AVG(ast) as avg_ast FROM match_stats WHERE player_id=? AND confirmed=1");
    $avg->execute([$pid]);
    $d = $avg->fetch(PDO::FETCH_ASSOC);
    $level = min(99,round(($d['avg_pts']*1.5)+($d['avg_reb']*1.2)+($d['avg_ast']*1.0)+30));
    $value = $level * 60000;
    $pdo->prepare("UPDATE users SET level=?,value=? WHERE id=?")->execute([$level,$value,$pid]);
    echo json_encode(['success'=>true,'message'=>'Stats confirmees et niveau mis a jour']);
}

// SAISIR STATS (manager ou admin seulement)
if ($action === 'submit_stats') {
    if (!isset($_SESSION['role'])||!in_array($_SESSION['role'],['admin','manager'])) {
        echo json_encode(['success'=>false,'message'=>'Seul le responsable peut saisir les stats']); exit;
    }
    $match_id = $_POST['match_id'];
    $player_id = $_POST['player_id'];
    $check = $pdo->prepare("SELECT id FROM match_stats WHERE match_id=? AND player_id=?");
    $check->execute([$match_id,$player_id]);
    if ($check->fetch()) {
        $stmt = $pdo->prepare("UPDATE match_stats SET pts=?,reb=?,ast=?,stl=?,blk=?,fg_made=?,fg_attempted=?,confirmed=1 WHERE match_id=? AND player_id=?");
        $stmt->execute([$_POST['pts']??0,$_POST['reb']??0,$_POST['ast']??0,$_POST['stl']??0,$_POST['blk']??0,$_POST['fg_made']??0,$_POST['fg_attempted']??0,$match_id,$player_id]);
    } else {
        $stmt = $pdo->prepare("INSERT INTO match_stats (match_id,player_id,pts,reb,ast,stl,blk,fg_made,fg_attempted,confirmed) VALUES (?,?,?,?,?,?,?,?,?,1)");
        $stmt->execute([$match_id,$player_id,$_POST['pts']??0,$_POST['reb']??0,$_POST['ast']??0,$_POST['stl']??0,$_POST['blk']??0,$_POST['fg_made']??0,$_POST['fg_attempted']??0]);
    }
    // Recalcul niveau
    $avg = $pdo->prepare("SELECT AVG(pts) as avg_pts,AVG(reb) as avg_reb,AVG(ast) as avg_ast FROM match_stats WHERE player_id=? AND confirmed=1");
    $avg->execute([$player_id]);
    $d = $avg->fetch(PDO::FETCH_ASSOC);
    $level = min(99,round(($d['avg_pts']*1.5)+($d['avg_reb']*1.2)+($d['avg_ast']*1.0)+30));
    $value = $level * 60000;
    $pdo->prepare("UPDATE users SET level=?,value=? WHERE id=?")->execute([$level,$value,$player_id]);
    echo json_encode(['success'=>true,'message'=>'Stats enregistrees']);
}

// HISTORIQUE STATS D'UN JOUEUR
if ($action === 'player_history') {
    $pid = $_GET['player_id']??'';
    $stmt = $pdo->prepare("SELECT ms.*,m.match_date,m.home_team,m.away_team FROM match_stats ms JOIN matches m ON m.id=ms.match_id WHERE ms.player_id=? AND ms.confirmed=1 ORDER BY m.match_date DESC");
    $stmt->execute([$pid]);
    echo json_encode(['success'=>true,'data'=>$stmt->fetchAll(PDO::FETCH_ASSOC)]);
}

// STATS EN ATTENTE
if ($action === 'pending_stats') {
    if (!isset($_SESSION['role'])||!in_array($_SESSION['role'],['admin','manager'])) {
        echo json_encode(['success'=>false,'message'=>'Acces refuse']); exit;
    }
    $team = $_SESSION['team']??'';
    if ($_SESSION['role']==='manager'&&$team) {
        $stmt = $pdo->prepare("SELECT ms.*,u.name as player_name,u.team,m.match_date FROM match_stats ms JOIN users u ON u.id=ms.player_id JOIN matches m ON m.id=ms.match_id WHERE ms.confirmed=0 AND u.team=? ORDER BY ms.created_at DESC");
        $stmt->execute([$team]);
    } else {
        $stmt = $pdo->query("SELECT ms.*,u.name as player_name,u.team,m.match_date FROM match_stats ms JOIN users u ON u.id=ms.player_id JOIN matches m ON m.id=ms.match_id WHERE ms.confirmed=0 ORDER BY ms.created_at DESC");
    }
    echo json_encode(['success'=>true,'data'=>$stmt->fetchAll(PDO::FETCH_ASSOC)]);
}
