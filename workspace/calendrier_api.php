<?php
require_once __DIR__ . '/includes/config.php';
require_once __DIR__ . '/includes/auth.php';

startSecureSession();
header('Content-Type: application/json');

requireAuth();
$user = currentUser();
$db   = getDB();
$action = $_REQUEST['action'] ?? '';

// ============ LISTE DES EVENEMENTS ============
if ($action === 'liste_evenements') {
    $debut = $_GET['debut'] ?? date('Y-m-01');
    $fin   = $_GET['fin']   ?? date('Y-m-t');

    // Événements manuels
    $sql = "SELECT e.*,
                   CONCAT(u.prenom,' ',u.nom) as createur_nom,
                   p.nom as projet_nom
            FROM evenements e
            LEFT JOIN users u ON u.id = e.createur_id
            LEFT JOIN projets p ON p.id = e.projet_id
            WHERE e.debut <= ? AND e.fin >= ?
            ORDER BY e.debut ASC";
    $stmt = $db->prepare($sql);
    $stmt->execute([$fin . ' 23:59:59', $debut . ' 00:00:00']);
    $events = $stmt->fetchAll();

    // Synchroniser tâches avec échéances
    $sqlT = "SELECT t.id, t.titre, t.date_echeance, t.statut, t.priorite,
                    p.nom as projet_nom,
                    CONCAT(u.prenom,' ',u.nom) as assigne_nom
             FROM taches t
             LEFT JOIN projets p ON p.id = t.projet_id
             LEFT JOIN users u ON u.id = t.assigne_a
             WHERE t.date_echeance BETWEEN ? AND ?
             AND t.statut != 'Terminé'";
    $stmtT = $db->prepare($sqlT);
    $stmtT->execute([$debut, $fin]);
    $taches = $stmtT->fetchAll();

    foreach ($taches as $t) {
        $events[] = [
            'id'           => 'tache_' . $t['id'],
            'titre'        => $t['titre'],
            'description'  => 'Tâche assignée à : ' . ($t['assigne_nom'] ?? 'Non assigné'),
            'type'         => 'tache',
            'couleur'      => $t['priorite'] === 'Haute' ? '#e05252' : ($t['priorite'] === 'Moyenne' ? '#f0a500' : '#36A9E1'),
            'debut'        => $t['date_echeance'] . ' 08:00:00',
            'fin'          => $t['date_echeance'] . ' 09:00:00',
            'toute_journee'=> 1,
            'projet_nom'   => $t['projet_nom'],
            'source'       => 'tache',
            'statut'       => $t['statut'],
        ];
    }

    // Synchroniser deadlines projets
    $sqlP = "SELECT id, nom, date_livraison, statut FROM projets
             WHERE date_livraison BETWEEN ? AND ?
             AND statut NOT IN ('Clôturé','Livré')";
    $stmtP = $db->prepare($sqlP);
    $stmtP->execute([$debut, $fin]);
    $projets = $stmtP->fetchAll();

    foreach ($projets as $p) {
        $events[] = [
            'id'           => 'projet_' . $p['id'],
            'titre'        => 'Deadline : ' . $p['nom'],
            'description'  => 'Livraison du projet — Statut : ' . $p['statut'],
            'type'         => 'deadline',
            'couleur'      => '#9b8fff',
            'debut'        => $p['date_livraison'] . ' 00:00:00',
            'fin'          => $p['date_livraison'] . ' 23:59:59',
            'toute_journee'=> 1,
            'source'       => 'projet',
        ];
    }

    echo json_encode($events);
    exit;
}

// ============ CREER UN EVENEMENT ============
if ($action === 'creer_evenement') {
    $titre      = trim($_POST['titre'] ?? '');
    $description= trim($_POST['description'] ?? '');
    $type       = $_POST['type'] ?? 'reunion';
    $couleur    = $_POST['couleur'] ?? '#36A9E1';
    $debut      = $_POST['debut'] ?? '';
    $fin        = $_POST['fin'] ?? '';
    $toute_j    = (int)($_POST['toute_journee'] ?? 0);
    $lieu       = trim($_POST['lieu'] ?? '');
    $lien       = trim($_POST['lien'] ?? '');
    $projet_id  = $_POST['projet_id'] ?: null;
    $recurrence = $_POST['recurrence'] ?? 'aucune';
    $participants = $_POST['participants'] ?? '[]';

    if (!$titre || !$debut || !$fin) {
        echo json_encode(['error' => 'Titre, début et fin sont obligatoires.']); exit;
    }

    $db->prepare("INSERT INTO evenements
        (titre, description, type, couleur, debut, fin, toute_journee, lieu, lien, projet_id, createur_id, participants, recurrence, source)
        VALUES (?,?,?,?,?,?,?,?,?,?,?,?,?,'manuel')")
       ->execute([$titre,$description,$type,$couleur,$debut,$fin,$toute_j,$lieu,$lien,$projet_id,$user['id'],$participants,$recurrence]);

    echo json_encode(['success' => true, 'id' => $db->lastInsertId()]);
    exit;
}

// ============ MODIFIER UN EVENEMENT ============
if ($action === 'modifier_evenement') {
    $id = (int)($_POST['id'] ?? 0);
    // Vérifier que ce n'est pas un événement synchronisé
    if (!$id) { echo json_encode(['error' => 'Événement introuvable.']); exit; }

    $db->prepare("UPDATE evenements SET
        titre=?, description=?, type=?, couleur=?, debut=?, fin=?,
        toute_journee=?, lieu=?, lien=?, participants=?, updated_at=NOW()
        WHERE id=? AND (createur_id=? OR ?)")
       ->execute([
           $_POST['titre'], $_POST['description'] ?? '', $_POST['type'] ?? 'reunion',
           $_POST['couleur'] ?? '#36A9E1', $_POST['debut'], $_POST['fin'],
           (int)($_POST['toute_journee'] ?? 0), $_POST['lieu'] ?? '', $_POST['lien'] ?? '',
           $_POST['participants'] ?? '[]', $id, $user['id'], isAdmin() ? 1 : 0
       ]);

    echo json_encode(['success' => true]);
    exit;
}

// ============ SUPPRIMER UN EVENEMENT ============
if ($action === 'supprimer_evenement') {
    $id = (int)($_POST['id'] ?? 0);
    $stmt = $db->prepare("SELECT createur_id FROM evenements WHERE id=?");
    $stmt->execute([$id]);
    $ev = $stmt->fetch();
    if (!$ev) { echo json_encode(['error' => 'Introuvable.']); exit; }
    if ($ev['createur_id'] != $user['id'] && !isAdmin()) {
        echo json_encode(['error' => 'Non autorisé.']); exit;
    }
    $db->prepare("DELETE FROM evenements WHERE id=?")->execute([$id]);
    echo json_encode(['success' => true]);
    exit;
}

// ============ DETAIL D'UN EVENEMENT ============
if ($action === 'detail_evenement') {
    $id = (int)($_GET['id'] ?? 0);
    $stmt = $db->prepare("SELECT e.*, CONCAT(u.prenom,' ',u.nom) as createur_nom, p.nom as projet_nom
                          FROM evenements e
                          LEFT JOIN users u ON u.id = e.createur_id
                          LEFT JOIN projets p ON p.id = e.projet_id
                          WHERE e.id=?");
    $stmt->execute([$id]);
    echo json_encode($stmt->fetch());
    exit;
}

// ============ LISTE DES UTILISATEURS (pour participants) ============
if ($action === 'liste_users') {
    $users = $db->query("SELECT id, CONCAT(prenom,' ',nom) as nom_complet, role FROM users WHERE actif=1 ORDER BY prenom")->fetchAll();
    echo json_encode($users);
    exit;
}

// ============ LISTE DES PROJETS (pour lier un événement) ============
if ($action === 'liste_projets') {
    $projets = $db->query("SELECT id, nom FROM projets WHERE statut NOT IN ('Clôturé') ORDER BY nom")->fetchAll();
    echo json_encode($projets);
    exit;
}

echo json_encode(['error' => 'Action inconnue : ' . $action]);
