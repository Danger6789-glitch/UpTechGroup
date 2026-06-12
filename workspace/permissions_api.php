<?php
require_once __DIR__ . '/includes/config.php';
require_once __DIR__ . '/includes/auth.php';
startSecureSession();
header('Content-Type: application/json');
requireAuth();
if (!isAdmin()) { echo json_encode(['error'=>'Accès non autorisé']); exit; }

$db     = getDB();
$action = $_POST['action'] ?? $_GET['action'] ?? '';

// Créer ou réparer la table
try {
    // Essayer d'abord de créer complète
    $db->exec("CREATE TABLE IF NOT EXISTS user_permissions (
        id INT AUTO_INCREMENT PRIMARY KEY,
        user_id INT NOT NULL,
        module VARCHAR(50) NOT NULL,
        peut_voir TINYINT(1) DEFAULT 1,
        UNIQUE KEY uk_user_module (user_id, module)
    ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4");
} catch(Exception $e) {}

// Ajouter la colonne peut_voir si elle manque
try {
    $db->exec("ALTER TABLE user_permissions ADD COLUMN peut_voir TINYINT(1) DEFAULT 1");
} catch(Exception $e) {
    // Colonne déjà présente — normal, on ignore
}

// Ajouter la contrainte FK si possible
try {
    $db->exec("ALTER TABLE user_permissions ADD FOREIGN KEY (user_id) REFERENCES users(id) ON DELETE CASCADE");
} catch(Exception $e) {}

if ($action === 'save_permissions') {
    $userId  = (int)($_POST['user_id'] ?? 0);
    $modules = json_decode($_POST['modules'] ?? '[]', true);

    if (!$userId) { echo json_encode(['error'=>'Utilisateur invalide']); exit; }

    $stmt = $db->prepare("SELECT role FROM users WHERE id=?");
    $stmt->execute([$userId]);
    $u = $stmt->fetch();
    if (!$u) { echo json_encode(['error'=>'Utilisateur introuvable']); exit; }

    if (in_array($u['role'], ['admin','manager'])) {
        echo json_encode(['success'=>true,'message'=>'Admin/Manager : accès complet automatique']); exit;
    }

    try {
        $db->prepare("DELETE FROM user_permissions WHERE user_id=?")->execute([$userId]);

        if (!empty($modules)) {
            $valid = ['dashboard','taches','calendrier','chat','fichiers','temps','assistant',
                      'projets','clients','crm','finances','facturation','charge',
                      'rapports','export','stats'];
            $s = $db->prepare("INSERT INTO user_permissions (user_id, module, peut_voir) VALUES (?, ?, 1)");
            foreach ($modules as $m) {
                if (in_array($m, $valid)) $s->execute([$userId, $m]);
            }
        }
        echo json_encode(['success'=>true]);
    } catch(Exception $e) {
        echo json_encode(['error'=>$e->getMessage()]);
    }
    exit;
}

if ($action === 'get_permissions') {
    $userId = (int)($_GET['user_id'] ?? 0);
    try {
        $stmt = $db->prepare("SELECT module FROM user_permissions WHERE user_id=? AND peut_voir=1");
        $stmt->execute([$userId]);
        echo json_encode(['permissions'=>$stmt->fetchAll(PDO::FETCH_COLUMN)]);
    } catch(Exception $e) {
        echo json_encode(['permissions'=>[]]);
    }
    exit;
}

echo json_encode(['error'=>'Action inconnue']);
