<?php
define('DB_HOST', 'localhost');
define('DB_NAME', 'c2761235c_uptechgroup_ws');
define('DB_USER', 'c2761235c_uptechgroup');
define('DB_PASS', 'UpTech2026db');
define('DB_CHARSET', 'utf8mb4');
define('APP_NAME', 'UP TECH GROUP');
define('APP_URL', 'https://uptech-group.com/workspace');
define('APP_VERSION', '1.0.0');
define('SESSION_LIFETIME', 3600 * 8);
define('TIMEZONE', 'Africa/Lome');
date_default_timezone_set(TIMEZONE);

function getDB(): PDO {
    static $pdo = null;
    if ($pdo === null) {
        try {
            $dsn = "mysql:host=" . DB_HOST . ";dbname=" . DB_NAME . ";charset=" . DB_CHARSET;
            $pdo = new PDO($dsn, DB_USER, DB_PASS, [
                PDO::ATTR_ERRMODE            => PDO::ERRMODE_EXCEPTION,
                PDO::ATTR_DEFAULT_FETCH_MODE => PDO::FETCH_ASSOC,
                PDO::ATTR_EMULATE_PREPARES   => false,
            ]);
        } catch (PDOException $e) {
            die(json_encode(['error' => 'Connexion base de données impossible.']));
        }
    }
    return $pdo;
}

define('OPENROUTER_API_KEY', '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');