<?php
define('DB_HOST', 'localhost');
define('DB_NAME', 'c2761235c_bba');
define('DB_USER', 'c2761235c_bba_admin');
define('DB_PASS', 'P={8AIuy&V%Mz!P9');

try {
    $pdo = new PDO(
        "mysql:host=".DB_HOST.";dbname=".DB_NAME.";charset=utf8",
        DB_USER,
        DB_PASS
    );
    $pdo->setAttribute(PDO::ATTR_ERRMODE, PDO::ERRMODE_EXCEPTION);
} catch(PDOException $e) {
    die(json_encode(['error' => 'Connexion echouee']));
}

session_start();
?>
