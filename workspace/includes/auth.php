<?php
require_once __DIR__ . '/config.php';

function startSecureSession(): void {
    if (session_status() === PHP_SESSION_NONE) {
        ini_set('session.cookie_httponly', 1);
        ini_set('session.use_strict_mode', 1);
        session_start();
    }
}

function login(string $email, string $password): array {
    $db = getDB();

    // Rate limiting — try/catch complet, la table peut ne pas exister
    $ip = $_SERVER['REMOTE_ADDR'] ?? 'unknown';
    try {
        $db->exec("CREATE TABLE IF NOT EXISTS login_attempts (
            ip VARCHAR(45) PRIMARY KEY,
            tentatives INT DEFAULT 1,
            derniere TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP
        ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4");

        $stmt = $db->prepare("SELECT tentatives, derniere FROM login_attempts WHERE ip=?");
        $stmt->execute([$ip]);
        $attempt = $stmt->fetch();
        if ($attempt) {
            $minutesEcoulees = (time() - strtotime($attempt['derniere'])) / 60;
            if ($minutesEcoulees >= 60) {
                $db->prepare("DELETE FROM login_attempts WHERE ip=?")->execute([$ip]);
            } elseif ($attempt['tentatives'] >= 10) {
                $restant = round(60 - $minutesEcoulees);
                return ['success'=>false,'message'=>"Trop de tentatives. Réessayez dans {$restant} minute(s)."];
            }
        }
    } catch(Exception $e) {
        // Pas bloquant — on continue sans rate limiting
    }

    // Vérification identifiants
    try {
        $stmt = $db->prepare("SELECT * FROM users WHERE email=? AND actif=1 LIMIT 1");
        $stmt->execute([$email]);
        $user = $stmt->fetch();
    } catch(Exception $e) {
        return ['success'=>false,'message'=>'Erreur de base de données.'];
    }

    if (!$user || !password_verify($password, $user['password'])) {
        // Enregistrer tentative échouée
        try {
            $db->prepare("INSERT INTO login_attempts (ip,tentatives) VALUES (?,1)
                ON DUPLICATE KEY UPDATE tentatives=tentatives+1, derniere=NOW()")
               ->execute([$ip]);
        } catch(Exception $e) {}
        return ['success'=>false,'message'=>'Email ou mot de passe incorrect.'];
    }

    // Succès — nettoyer les tentatives
    try {
        $db->prepare("DELETE FROM login_attempts WHERE ip=?")->execute([$ip]);
    } catch(Exception $e) {}

    // Régénérer la session (sécurité)
    session_regenerate_id(true);

    $_SESSION['user_id']       = $user['id'];
    $_SESSION['user_nom']      = $user['prenom'] . ' ' . $user['nom'];
    $_SESSION['user_role']     = $user['role'];
    $_SESSION['user_email']    = $user['email'];
    $_SESSION['login_time']    = time();
    $_SESSION['last_activity'] = time();

    try {
        $db->prepare("UPDATE users SET last_login=NOW() WHERE id=?")->execute([$user['id']]);
    } catch(Exception $e) {}

    // Vérifier 2FA
    $needs2fa = !empty($user['deux_fa_actif']);
    return [
        'success'      => true,
        'role'         => $user['role'],
        'requires_2fa' => $needs2fa,
    ];
}

function logout(): void {
    startSecureSession();
    session_unset();
    session_destroy();
    if (ini_get('session.use_cookies')) {
        $p = session_get_cookie_params();
        setcookie(session_name(), '', time()-42000, $p['path'], $p['domain'], $p['secure'], $p['httponly']);
    }
    header('Location: /workspace/index.php');
    exit;
}

function requireAuth(array $roles = []): void {
    startSecureSession();

    if (empty($_SESSION['user_id'])) {
        header('Location: /workspace/index.php?msg=session_expired');
        exit;
    }

    // Timeout basé sur l'activité
    $lifetime = defined('SESSION_LIFETIME') ? SESSION_LIFETIME : 28800;
    if (isset($_SESSION['last_activity']) && (time() - $_SESSION['last_activity']) > $lifetime) {
        logout();
    }
    $_SESSION['last_activity'] = time();

    if (!empty($roles) && !in_array($_SESSION['user_role'], $roles)) {
        header('Location: /workspace/dashboard.php');
        exit;
    }
}

function currentUser(): array {
    return [
        'id'    => $_SESSION['user_id']    ?? 0,
        'nom'   => $_SESSION['user_nom']   ?? '',
        'role'  => $_SESSION['user_role']  ?? '',
        'email' => $_SESSION['user_email'] ?? '',
    ];
}

function isAdmin(): bool   { return ($_SESSION['user_role'] ?? '') === 'admin'; }
function isManager(): bool { return in_array($_SESSION['user_role'] ?? '', ['admin','manager']); }

function addNotification(int $userId, string $message, string $lien = ''): void {
    try {
        $db = getDB();
        $db->prepare("INSERT INTO notifications (user_id,message,lien,created_at) VALUES (?,?,?,NOW())")
           ->execute([$userId, $message, $lien]);
    } catch(Exception $e) {}
}

function getUnreadNotifications(int $userId): array {
    try {
        $db = getDB();
        $stmt = $db->prepare("SELECT * FROM notifications WHERE user_id=? AND lu=0 ORDER BY created_at DESC LIMIT 20");
        $stmt->execute([$userId]);
        return $stmt->fetchAll();
    } catch(Exception $e) { return []; }
}

function markNotificationsRead(int $userId): void {
    try {
        $db = getDB();
        $db->prepare("UPDATE notifications SET lu=1 WHERE user_id=?")->execute([$userId]);
    } catch(Exception $e) {}
}

function logActivity(int $userId, string $action, string $detail = ''): void {
    try {
        $db = getDB();
        $db->exec("CREATE TABLE IF NOT EXISTS activity_log (
            id INT AUTO_INCREMENT PRIMARY KEY,
            user_id INT, action VARCHAR(100), detail TEXT,
            ip VARCHAR(45), created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
            INDEX(user_id), INDEX(created_at)
        ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4");
        $db->prepare("INSERT INTO activity_log (user_id,action,detail,ip) VALUES (?,?,?,?)")
           ->execute([$userId, $action, $detail, $_SERVER['REMOTE_ADDR'] ?? '']);
    } catch(Exception $e) {}
}
