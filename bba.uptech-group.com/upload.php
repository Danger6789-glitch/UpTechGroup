<?php
require_once 'config.php';
header('Content-Type: application/json');
$action = $_POST['action'] ?? $_GET['action'] ?? '';

// ACTIONS PUBLIQUES
if ($action === 'get_settings') {
    $stmt = $pdo->query("SELECT setting_key, setting_value FROM site_settings");
    $settings = [];
    foreach ($stmt->fetchAll(PDO::FETCH_ASSOC) as $row) $settings[$row['setting_key']] = $row['setting_value'];
    echo json_encode(['success'=>true,'data'=>$settings]); exit;
}

if ($action === 'list_articles') {
    $status = $_GET['status'] ?? 'published';
    $cat = $_GET['category'] ?? '';
    if ($cat) {
        $stmt = $pdo->prepare("SELECT a.*,m.filename FROM articles a LEFT JOIN media m ON m.id=a.image_id WHERE a.status=? AND a.category=? ORDER BY a.created_at DESC");
        $stmt->execute([$status,$cat]);
    } else {
        $stmt = $pdo->prepare("SELECT a.*,m.filename FROM articles a LEFT JOIN media m ON m.id=a.image_id WHERE a.status=? ORDER BY a.created_at DESC");
        $stmt->execute([$status]);
    }
    echo json_encode(['success'=>true,'data'=>$stmt->fetchAll(PDO::FETCH_ASSOC)]); exit;
}

// ACTIONS ADMIN UNIQUEMENT
if (!isset($_SESSION['user_id']) || $_SESSION['role'] !== 'admin') {
    echo json_encode(['success'=>false,'message'=>'Accès refusé']); exit;
}

if ($action === 'upload_image') {
    if (!isset($_FILES['image']) || $_FILES['image']['error'] !== 0) {
        echo json_encode(['success'=>false,'message'=>'Aucun fichier reçu']); exit;
    }
    $file = $_FILES['image'];
    $allowed = ['image/jpeg','image/png','image/webp','image/gif'];
    if (!in_array($file['type'],$allowed)) { echo json_encode(['success'=>false,'message'=>'Format non autorisé (JPG, PNG, WEBP)']); exit; }
    if ($file['size'] > 5*1024*1024) { echo json_encode(['success'=>false,'message'=>'Fichier trop lourd (max 5 Mo)']); exit; }
    $ext = strtolower(pathinfo($file['name'], PATHINFO_EXTENSION));
    $filename = uniqid('bba_') . '.' . $ext;
    $uploadDir = __DIR__ . '/assets/';
    if (!is_dir($uploadDir)) mkdir($uploadDir, 0755, true);
    if (!move_uploaded_file($file['tmp_name'], $uploadDir . $filename)) {
        echo json_encode(['success'=>false,'message'=>'Erreur lors de l\'upload']); exit;
    }
    $type = $_POST['type'] ?? 'image';
    $stmt = $pdo->prepare("INSERT INTO media (filename, original_name, type, uploaded_by) VALUES (?,?,?,?)");
    $stmt->execute([$filename, $file['name'], $type, $_SESSION['user_id']]);
    $id = $pdo->lastInsertId();
    echo json_encode(['success'=>true,'filename'=>$filename,'url'=>'assets/'.$filename,'id'=>$id]);
}

if ($action === 'list_media') {
    $stmt = $pdo->query("SELECT * FROM media ORDER BY created_at DESC");
    echo json_encode(['success'=>true,'data'=>$stmt->fetchAll(PDO::FETCH_ASSOC)]);
}

if ($action === 'delete_media') {
    $stmt = $pdo->prepare("SELECT filename FROM media WHERE id=?");
    $stmt->execute([$_POST['id']]);
    $file = $stmt->fetch(PDO::FETCH_ASSOC);
    if ($file) {
        $path = __DIR__.'/assets/'.$file['filename'];
        if (file_exists($path)) unlink($path);
        $pdo->prepare("DELETE FROM media WHERE id=?")->execute([$_POST['id']]);
    }
    echo json_encode(['success'=>true]);
}

if ($action === 'save_article') {
    $id = $_POST['id'] ?? null;
    $data = [
        $_POST['title'] ?? '',
        $_POST['content'] ?? '',
        $_POST['image_id'] ? (int)$_POST['image_id'] : null,
        $_POST['status'] ?? 'draft',
        $_POST['category'] ?? 'actualite',
        $_POST['article_type'] ?? 'simple',
    ];
    if ($id) {
        $stmt = $pdo->prepare("UPDATE articles SET title=?,content=?,image_id=?,status=?,category=?,article_type=? WHERE id=?");
        $data[] = $id;
    } else {
        $data[] = $_SESSION['user_id'];
        $stmt = $pdo->prepare("INSERT INTO articles (title,content,image_id,status,category,article_type,author) VALUES (?,?,?,?,?,?,?)");
    }
    $stmt->execute($data);
    echo json_encode(['success'=>true,'message'=>'Article sauvegardé','id'=>$id ?: $pdo->lastInsertId()]);
}

if ($action === 'list_articles_admin') {
    $stmt = $pdo->query("SELECT a.*,m.filename FROM articles a LEFT JOIN media m ON m.id=a.image_id ORDER BY a.created_at DESC");
    echo json_encode(['success'=>true,'data'=>$stmt->fetchAll(PDO::FETCH_ASSOC)]);
}

if ($action === 'delete_article') {
    $pdo->prepare("DELETE FROM articles WHERE id=?")->execute([$_POST['id']]);
    echo json_encode(['success'=>true]);
}

if ($action === 'save_setting') {
    $stmt = $pdo->prepare("INSERT INTO site_settings (setting_key,setting_value) VALUES (?,?) ON DUPLICATE KEY UPDATE setting_value=?");
    $stmt->execute([$_POST['key'],$_POST['value'],$_POST['value']]);
    echo json_encode(['success'=>true]);
}

if ($action === 'save_settings_bulk') {
    $settings = json_decode($_POST['settings'] ?? '{}', true);
    foreach ($settings as $key => $value) {
        $stmt = $pdo->prepare("INSERT INTO site_settings (setting_key,setting_value) VALUES (?,?) ON DUPLICATE KEY UPDATE setting_value=?");
        $stmt->execute([$key,$value,$value]);
    }
    echo json_encode(['success'=>true,'message'=>'Paramètres sauvegardés']);
}

if ($action === 'upload_player_photo') {
    if (!isset($_SESSION['user_id'])) {
        echo json_encode(['success'=>false,'message'=>'Non connecté']); exit;
    }
    if (!isset($_FILES['image']) || $_FILES['image']['error'] !== 0) {
        echo json_encode(['success'=>false,'message'=>'Aucun fichier reçu']); exit;
    }
    $file = $_FILES['image'];
    $allowed = ['image/jpeg','image/png','image/webp'];
    if (!in_array($file['type'],$allowed)) {
        echo json_encode(['success'=>false,'message'=>'Format non autorisé']); exit;
    }
    if ($file['size'] > 2*1024*1024) {
        echo json_encode(['success'=>false,'message'=>'Fichier trop lourd (max 2 Mo)']); exit;
    }
    $ext = strtolower(pathinfo($file['name'],PATHINFO_EXTENSION));
    $filename = 'player_'.$_SESSION['user_id'].'.'.$ext;
    $uploadDir = __DIR__.'/assets/';
    if (!is_dir($uploadDir)) mkdir($uploadDir,0755,true);
    if (!move_uploaded_file($file['tmp_name'],$uploadDir.$filename)) {
        echo json_encode(['success'=>false,'message'=>'Erreur upload']); exit;
    }
    $pdo->prepare("UPDATE users SET photo=? WHERE id=?")->execute([$filename,$_SESSION['user_id']]);
    echo json_encode(['success'=>true,'filename'=>$filename,'url'=>'assets/'.$filename]);
}