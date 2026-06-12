<?php
require_once __DIR__ . '/includes/config.php';
require_once __DIR__ . '/includes/auth.php';
startSecureSession();
header('Content-Type: application/json');
requireAuth();
$user   = currentUser();
$db     = getDB();
$action = $_REQUEST['action'] ?? '';

function fmtDuration(int $sec): string {
    $h = floor($sec / 3600);
    $m = floor(($sec % 3600) / 60);
    $s = $sec % 60;
    return ($h > 0 ? $h.'h ' : '') . ($m > 0 ? $m.'m ' : '') . $s.'s';
}

// ============ DÉMARRER LE TIMER ============
if ($action === 'start') {
    // Vérifier si un timer est déjà actif
    $stmt = $db->prepare("SELECT * FROM time_active WHERE user_id=?");
    $stmt->execute([$user['id']]);
    if ($stmt->fetch()) {
        echo json_encode(['error' => 'Un timer est déjà actif. Arrêtez-le avant d\'en démarrer un nouveau.']); exit;
    }

    $tacheId   = $_POST['tache_id'] ?: null;
    $projetId  = $_POST['projet_id'] ?: null;
    $desc      = trim($_POST['description'] ?? '');
    $debut     = date('Y-m-d H:i:s');

    // Si tâche sélectionnée, récupérer le projet associé
    if ($tacheId && !$projetId) {
        $s = $db->prepare("SELECT projet_id FROM taches WHERE id=?");
        $s->execute([$tacheId]);
        $t = $s->fetch();
        if ($t) $projetId = $t['projet_id'];
    }

    $db->prepare("INSERT INTO time_active (user_id,tache_id,projet_id,description,debut) VALUES (?,?,?,?,?)")
       ->execute([$user['id'], $tacheId, $projetId, $desc, $debut]);

    echo json_encode(['success'=>true, 'debut'=>$debut]); exit;
}

// ============ ARRÊTER LE TIMER ============
if ($action === 'stop') {
    $stmt = $db->prepare("SELECT * FROM time_active WHERE user_id=?");
    $stmt->execute([$user['id']]);
    $active = $stmt->fetch();

    if (!$active) { echo json_encode(['error'=>'Aucun timer actif.']); exit; }

    $fin    = date('Y-m-d H:i:s');
    $duree  = time() - strtotime($active['debut']);

    $db->prepare("INSERT INTO time_entries (user_id,tache_id,projet_id,description,debut,fin,duree,facturable) VALUES (?,?,?,?,?,?,?,?)")
       ->execute([$user['id'], $active['tache_id'], $active['projet_id'], $active['description'], $active['debut'], $fin, $duree, 1]);

    $entryId = $db->lastInsertId();
    $db->prepare("DELETE FROM time_active WHERE user_id=?")->execute([$user['id']]);

    echo json_encode(['success'=>true, 'id'=>$entryId, 'duree'=>$duree, 'duree_fmt'=>fmtDuration($duree)]); exit;
}

// ============ TIMER ACTIF ============
if ($action === 'actif') {
    $stmt = $db->prepare("SELECT a.*, t.titre as tache_titre, p.nom as projet_nom
                          FROM time_active a
                          LEFT JOIN taches t ON t.id=a.tache_id
                          LEFT JOIN projets p ON p.id=a.projet_id
                          WHERE a.user_id=?");
    $stmt->execute([$user['id']]);
    $active = $stmt->fetch();
    if ($active) {
        $active['elapsed'] = time() - strtotime($active['debut']);
        $active['elapsed_fmt'] = fmtDuration($active['elapsed']);
    }
    echo json_encode($active ?: null); exit;
}

// ============ LISTE DES ENTRÉES ============
if ($action === 'liste') {
    $periode = $_GET['periode'] ?? 'semaine';
    $userId  = isManager() && !empty($_GET['user_id']) ? (int)$_GET['user_id'] : $user['id'];

    switch($periode) {
        case 'aujourd_hui': $depuis = date('Y-m-d 00:00:00'); break;
        case 'semaine':     $depuis = date('Y-m-d 00:00:00', strtotime('monday this week')); break;
        case 'mois':        $depuis = date('Y-m-01 00:00:00'); break;
        case 'tout':        $depuis = '2000-01-01 00:00:00'; break;
        default:            $depuis = date('Y-m-d 00:00:00', strtotime('monday this week'));
    }

    $whereUser = isManager() && empty($_GET['user_id']) ? '' : "AND e.user_id=$userId";
    $sql = "SELECT e.*, CONCAT(u.prenom,' ',u.nom) as user_nom,
                   t.titre as tache_titre, p.nom as projet_nom
            FROM time_entries e
            LEFT JOIN users u ON u.id=e.user_id
            LEFT JOIN taches t ON t.id=e.tache_id
            LEFT JOIN projets p ON p.id=e.projet_id
            WHERE e.debut >= ? $whereUser
            ORDER BY e.debut DESC LIMIT 100";
    $stmt = $db->prepare($sql);
    $stmt->execute([$depuis]);
    $entries = $stmt->fetchAll();

    foreach ($entries as &$e) {
        $e['duree_fmt'] = fmtDuration((int)$e['duree']);
    }
    echo json_encode($entries); exit;
}

// ============ STATS ============
if ($action === 'stats') {
    $uid = $user['id'];

    // Total aujourd'hui
    $auj = $db->prepare("SELECT COALESCE(SUM(duree),0) FROM time_entries WHERE user_id=? AND DATE(debut)=CURDATE()");
    $auj->execute([$uid]); $aujTotal = (int)$auj->fetchColumn();

    // Total semaine
    $sem = $db->prepare("SELECT COALESCE(SUM(duree),0) FROM time_entries WHERE user_id=? AND debut >= ?");
    $sem->execute([$uid, date('Y-m-d', strtotime('monday this week'))]); $semTotal = (int)$sem->fetchColumn();

    // Total mois
    $mois = $db->prepare("SELECT COALESCE(SUM(duree),0) FROM time_entries WHERE user_id=? AND MONTH(debut)=MONTH(NOW()) AND YEAR(debut)=YEAR(NOW())");
    $mois->execute([$uid]); $moisTotal = (int)$mois->fetchColumn();

    // Par projet cette semaine
    $projets = $db->prepare("SELECT p.nom, COALESCE(SUM(e.duree),0) as total
                             FROM time_entries e
                             LEFT JOIN projets p ON p.id=e.projet_id
                             WHERE e.user_id=? AND e.debut >= ?
                             GROUP BY e.projet_id, p.nom
                             ORDER BY total DESC LIMIT 5");
    $projets->execute([$uid, date('Y-m-d', strtotime('monday this week'))]);
    $parProjet = $projets->fetchAll();
    foreach ($parProjet as &$p) { $p['total_fmt'] = fmtDuration((int)$p['total']); }

    // Timer actif
    $actifStmt = $db->prepare("SELECT debut FROM time_active WHERE user_id=?");
    $actifStmt->execute([$uid]);
    $actif = $actifStmt->fetch();
    $elapsed = $actif ? time() - strtotime($actif['debut']) : 0;

    echo json_encode([
        'aujourd_hui'  => $aujTotal,
        'semaine'      => $semTotal,
        'mois'         => $moisTotal,
        'auj_fmt'      => fmtDuration($aujTotal),
        'sem_fmt'      => fmtDuration($semTotal),
        'mois_fmt'     => fmtDuration($moisTotal),
        'par_projet'   => $parProjet,
        'timer_actif'  => $actif ? true : false,
        'elapsed'      => $elapsed,
    ]); exit;
}

// ============ MODIFIER UNE ENTRÉE ============
if ($action === 'modifier') {
    $id   = (int)($_POST['id'] ?? 0);
    $desc = trim($_POST['description'] ?? '');
    $fact = (int)($_POST['facturable'] ?? 1);
    $db->prepare("UPDATE time_entries SET description=?,facturable=? WHERE id=? AND user_id=?")
       ->execute([$desc, $fact, $id, $user['id']]);
    echo json_encode(['success'=>true]); exit;
}

// ============ SUPPRIMER UNE ENTRÉE ============
if ($action === 'supprimer') {
    $id = (int)($_POST['id'] ?? 0);
    $stmt = $db->prepare("SELECT user_id FROM time_entries WHERE id=?");
    $stmt->execute([$id]);
    $e = $stmt->fetch();
    if (!$e || ($e['user_id'] != $user['id'] && !isAdmin())) {
        echo json_encode(['error'=>'Non autorisé']); exit;
    }
    $db->prepare("DELETE FROM time_entries WHERE id=?")->execute([$id]);
    echo json_encode(['success'=>true]); exit;
}

// ============ RAPPORT PAR PROJET (Manager) ============
if ($action === 'rapport_projet' && isManager()) {
    $projetId = (int)($_GET['projet_id'] ?? 0);
    $sql = "SELECT CONCAT(u.prenom,' ',u.nom) as user_nom,
                   COALESCE(SUM(e.duree),0) as total,
                   COUNT(e.id) as nb_entrees,
                   COALESCE(SUM(CASE WHEN e.facturable=1 THEN e.duree ELSE 0 END),0) as facturable
            FROM time_entries e
            LEFT JOIN users u ON u.id=e.user_id
            WHERE e.projet_id=?
            GROUP BY e.user_id, u.prenom, u.nom
            ORDER BY total DESC";
    $stmt = $db->prepare($sql); $stmt->execute([$projetId]);
    $data = $stmt->fetchAll();
    foreach ($data as &$d) {
        $d['total_fmt']      = fmtDuration((int)$d['total']);
        $d['facturable_fmt'] = fmtDuration((int)$d['facturable']);
    }
    echo json_encode($data); exit;
}

// ============ LISTE TÂCHES (pour sélecteur) ============
if ($action === 'mes_taches_select') {
    $stmt = $db->prepare("SELECT t.id, t.titre, p.nom as projet_nom FROM taches t LEFT JOIN projets p ON p.id=t.projet_id WHERE t.assigne_a=? AND t.statut!='Terminé' ORDER BY t.titre ASC");
    $stmt->execute([$user['id']]);
    echo json_encode($stmt->fetchAll()); exit;
}

echo json_encode(['error'=>'Action inconnue']);
