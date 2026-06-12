<?php
require_once 'config.php';
header('Content-Type: application/json');

$action = $_POST['action'] ?? $_GET['action'] ?? '';

// DEMANDE D'INSCRIPTION D'ÉQUIPE (public)
if ($action === 'request_team') {
    $team_name = trim($_POST['team_name'] ?? '');
    $city = trim($_POST['city'] ?? 'Lomé');
    $color = $_POST['color'] ?? '#1D428A';
    $description = $_POST['description'] ?? '';
    $manager_name = trim($_POST['manager_name'] ?? '');
    $manager_email = trim($_POST['manager_email'] ?? '');
    $manager_phone = $_POST['manager_phone'] ?? '';
    $whatsapp = $_POST['whatsapp'] ?? '';

    if (!$team_name || !$manager_name || !$manager_email) {
        echo json_encode(['success' => false, 'message' => 'Champs obligatoires manquants']);
        exit;
    }

    // Vérifier si email déjà utilisé
    $check = $pdo->prepare("SELECT id FROM team_requests WHERE manager_email = ? AND status = 'pending'");
    $check->execute([$manager_email]);
    if ($check->fetch()) {
        echo json_encode(['success' => false, 'message' => 'Une demande existe déjà pour cet email']);
        exit;
    }

    $stmt = $pdo->prepare("INSERT INTO team_requests (team_name, city, color, description, manager_name, manager_email, manager_phone, whatsapp) VALUES (?,?,?,?,?,?,?,?)");
    $stmt->execute([$team_name, $city, $color, $description, $manager_name, $manager_email, $manager_phone, $whatsapp]);
    echo json_encode(['success' => true]);
}

// LISTE DES DEMANDES (admin uniquement)
if ($action === 'list_requests') {
    if (!isset($_SESSION['role']) || $_SESSION['role'] !== 'admin') {
        echo json_encode(['success' => false, 'message' => 'Accès refusé']); exit;
    }
    $status = $_GET['status'] ?? 'pending';
    $stmt = $pdo->prepare("SELECT * FROM team_requests WHERE status = ? ORDER BY submitted_at DESC");
    $stmt->execute([$status]);
    echo json_encode(['success' => true, 'data' => $stmt->fetchAll(PDO::FETCH_ASSOC)]);
}

// ACCEPTER UNE ÉQUIPE (admin uniquement)
if ($action === 'accept_team') {
    if (!isset($_SESSION['role']) || $_SESSION['role'] !== 'admin') {
        echo json_encode(['success' => false, 'message' => 'Accès refusé']); exit;
    }
    $req_id = $_POST['req_id'] ?? '';
    $password = $_POST['password'] ?? '';

    $req = $pdo->prepare("SELECT * FROM team_requests WHERE id = ?");
    $req->execute([$req_id]);
    $r = $req->fetch(PDO::FETCH_ASSOC);
    if (!$r) { echo json_encode(['success' => false, 'message' => 'Demande introuvable']); exit; }

    // Créer l'ID de l'équipe
    $team_id = strtolower(preg_replace('/[^a-zA-Z0-9]/', '', $r['team_name']));
    $team_id = substr($team_id, 0, 20);

    // Vérifier si l'ID existe déjà
    $check = $pdo->prepare("SELECT id FROM teams WHERE id = ?");
    $check->execute([$team_id]);
    if ($check->fetch()) $team_id .= rand(10, 99);

    // Créer l'équipe
    $stmt = $pdo->prepare("INSERT INTO teams (id, name, city, color, whatsapp, status) VALUES (?,?,?,?,?,'active')");
    $stmt->execute([$team_id, $r['team_name'], $r['city'], $r['color'], $r['whatsapp']]);

    // Créer le compte responsable
    $manager_id = uniqid('manager_');
    $hashed = hash('sha256', $password);
    $stmt2 = $pdo->prepare("INSERT INTO users (id, name, email, password, role, team, status) VALUES (?,?,?,?,'manager',?,'active')");
    $stmt2->execute([$manager_id, $r['manager_name'], $r['manager_email'], $hashed, $team_id]);

    // Lier le responsable à l'équipe
    $pdo->prepare("UPDATE teams SET manager_id = ? WHERE id = ?")->execute([$manager_id, $team_id]);

    // Mettre à jour le statut de la demande
    $pdo->prepare("UPDATE team_requests SET status = 'accepted' WHERE id = ?")->execute([$req_id]);

    echo json_encode(['success' => true, 'team_id' => $team_id, 'manager_email' => $r['manager_email']]);
}

// REFUSER UNE ÉQUIPE (admin uniquement)
if ($action === 'reject_team') {
    if (!isset($_SESSION['role']) || $_SESSION['role'] !== 'admin') {
        echo json_encode(['success' => false, 'message' => 'Accès refusé']); exit;
    }
    $req_id = $_POST['req_id'] ?? '';
    $pdo->prepare("UPDATE team_requests SET status = 'rejected' WHERE id = ?")->execute([$req_id]);
    echo json_encode(['success' => true]);
}

// LISTE DES ÉQUIPES ACTIVES
if ($action === 'list_teams') {
    $stmt = $pdo->query("SELECT * FROM teams WHERE status = 'active' ORDER BY name ASC");
    echo json_encode(['success' => true, 'data' => $stmt->fetchAll(PDO::FETCH_ASSOC)]);
}

// METTRE À JOUR UNE ÉQUIPE (admin)
if ($action === 'update_team') {
    if (!isset($_SESSION['role']) || $_SESSION['role'] !== 'admin') {
        echo json_encode(['success' => false, 'message' => 'Accès refusé']); exit;
    }
    $team_id = $_POST['team_id'] ?? '';
    $stmt = $pdo->prepare("UPDATE teams SET whatsapp=?, color=? WHERE id=?");
    $stmt->execute([$_POST['whatsapp'] ?? '', $_POST['color'] ?? '', $team_id]);
    if (!empty($_POST['logo'])) {
        $pdo->prepare("UPDATE teams SET logo=? WHERE id=?")->execute([$_POST['logo'], $team_id]);
    }
    echo json_encode(['success' => true]);
}

// TRADE (admin)
if ($action === 'trade_player') {
    if (!isset($_SESSION['role']) || $_SESSION['role'] !== 'admin') {
        echo json_encode(['success' => false, 'message' => 'Accès refusé']); exit;
    }
    $player_id = $_POST['player_id'] ?? '';
    $to_team = $_POST['to_team'] ?? '';
    $reason = $_POST['reason'] ?? '';
    $trade_date = $_POST['trade_date'] ?? date('Y-m-d');

    // Récupérer l'équipe actuelle
    $p = $pdo->prepare("SELECT team, name FROM users WHERE id = ?");
    $p->execute([$player_id]);
    $player = $p->fetch(PDO::FETCH_ASSOC);
    if (!$player) { echo json_encode(['success' => false, 'message' => 'Joueur introuvable']); exit; }

    $from_team = $player['team'];

    // Effectuer le trade
    $pdo->prepare("UPDATE users SET team = ? WHERE id = ?")->execute([$to_team, $player_id]);

    // Enregistrer le trade
    $pdo->prepare("INSERT INTO trades (player_id, from_team, to_team, reason, trade_date, created_by) VALUES (?,?,?,?,?,?)")
        ->execute([$player_id, $from_team, $to_team, $reason, $trade_date, $_SESSION['user_id']]);

    // Récupérer les noms des équipes
    $ft = $pdo->prepare("SELECT name FROM teams WHERE id = ?");
    $ft->execute([$from_team]);
    $from_name = $ft->fetchColumn() ?: $from_team;
    $tt = $pdo->prepare("SELECT name FROM teams WHERE id = ?");
    $tt->execute([$to_team]);
    $to_name = $tt->fetchColumn() ?: $to_team;

    // Créer un article automatique
    $title = 'TRADE : ' . $player['name'] . ' rejoint ' . $to_name;
    $content = $player['name'] . ' a été transféré de ' . $from_name . ' vers ' . $to_name . ' le ' . date('d/m/Y', strtotime($trade_date)) . '.' . ($reason ? ' Raison : ' . $reason : '');
    $pdo->prepare("INSERT INTO articles (title, content, status, category) VALUES (?,?,'published','trade')")
        ->execute([$title, $content]);

    echo json_encode(['success' => true, 'message' => 'Trade effectué et article publié automatiquement']);
}
