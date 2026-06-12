<?php
require_once __DIR__ . '/includes/config.php';
require_once __DIR__ . '/includes/auth.php';
startSecureSession();
header('Content-Type: application/json');
requireAuth();
$user   = currentUser();
$db     = getDB();
$action = $_REQUEST['action'] ?? '';

// ============ RECHERCHE GLOBALE ============
if ($action === 'search') {
    $q = trim($_GET['q'] ?? '');
    if (strlen($q) < 2) { echo json_encode(['results'=>[]]); exit; }

    $like   = '%' . $q . '%';
    $results = [];

    // Projets
    if (isManager()) {
        $stmt = $db->prepare("SELECT id, nom as titre, statut as meta, 'projet' as type FROM projets WHERE nom LIKE ? OR type_prestation LIKE ? LIMIT 5");
        $stmt->execute([$like, $like]);
    } else {
        $stmt = $db->prepare("SELECT DISTINCT p.id, p.nom as titre, p.statut as meta, 'projet' as type FROM projets p LEFT JOIN taches t ON t.projet_id=p.id WHERE t.assigne_a=? AND (p.nom LIKE ? OR p.type_prestation LIKE ?) LIMIT 5");
        $stmt->execute([$user['id'], $like, $like]);
    }
    foreach ($stmt->fetchAll() as $r) $results[] = $r;

    // Tâches
    $stmtT = $db->prepare("SELECT t.id, t.titre, t.statut as meta, 'tache' as type FROM taches t WHERE " . (isManager() ? '' : "t.assigne_a={$user['id']} AND ") . "(t.titre LIKE ? OR t.description LIKE ?) LIMIT 5");
    $stmtT->execute([$like, $like]);
    foreach ($stmtT->fetchAll() as $r) $results[] = $r;

    // Clients (managers seulement)
    if (isManager()) {
        $stmtC = $db->prepare("SELECT id, raison_sociale as titre, statut as meta, 'client' as type FROM clients WHERE raison_sociale LIKE ? OR contact_nom LIKE ? LIMIT 5");
        $stmtC->execute([$like, $like]);
        foreach ($stmtC->fetchAll() as $r) $results[] = $r;
    }

    // Utilisateurs
    $stmtU = $db->prepare("SELECT id, CONCAT(prenom,' ',nom) as titre, role as meta, 'user' as type FROM users WHERE actif=1 AND (prenom LIKE ? OR nom LIKE ? OR email LIKE ?) LIMIT 4");
    $stmtU->execute([$like, $like, $like]);
    foreach ($stmtU->fetchAll() as $r) $results[] = $r;

    // Messages chat
    $stmtM = $db->prepare("SELECT m.id, SUBSTRING(m.contenu,1,60) as titre, CONCAT(u.prenom,' ',u.nom) as meta, 'message' as type FROM chat_messages m LEFT JOIN users u ON u.id=m.expediteur_id WHERE m.contenu LIKE ? LIMIT 3");
    $stmtM->execute([$like]);
    foreach ($stmtM->fetchAll() as $r) $results[] = $r;

    echo json_encode(['results' => $results]); exit;
}

// ============ NOTIFICATIONS POLLING (SSE-style) ============
if ($action === 'notifs') {
    $since = (int)($_GET['since'] ?? 0);
    $stmt  = $db->prepare("SELECT * FROM notifications WHERE user_id=? AND id>? ORDER BY created_at DESC LIMIT 20");
    $stmt->execute([$user['id'], $since]);
    $notifs = $stmt->fetchAll();
    $unread = (int)$db->prepare("SELECT COUNT(*) FROM notifications WHERE user_id=? AND lu=0")->execute([$user['id']]) ? $db->query("SELECT COUNT(*) FROM notifications WHERE user_id={$user['id']} AND lu=0")->fetchColumn() : 0;
    echo json_encode(['notifs'=>$notifs,'unread'=>$unread,'last_id'=>$notifs?max(array_column($notifs,'id')):$since]); exit;
}

// ============ MARQUER LU ============
if ($action === 'read_notifs') {
    $db->prepare("UPDATE notifications SET lu=1 WHERE user_id=?")->execute([$user['id']]);
    echo json_encode(['success'=>true]); exit;
}

// ============ MARQUER UNE NOTIF LUE ============
if ($action === 'read_one') {
    $id = (int)($_POST['id'] ?? 0);
    $db->prepare("UPDATE notifications SET lu=1 WHERE id=? AND user_id=?")->execute([$id, $user['id']]);
    echo json_encode(['success'=>true]); exit;
}

// ============ STATS NOTIFS ============
if ($action === 'notif_count') {
    $stmt = $db->prepare("SELECT COUNT(*) FROM notifications WHERE user_id=? AND lu=0");
    $stmt->execute([$user['id']]);
    echo json_encode(['count'=>(int)$stmt->fetchColumn()]); exit;
}

echo json_encode(['error'=>'Action inconnue']);
