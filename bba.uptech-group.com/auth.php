<?php
require_once 'config.php';
header('Content-Type: application/json');

$action = $_POST['action'] ?? '';

if ($action === 'login') {
    $email = $_POST['email'] ?? '';
    $password = $_POST['password'] ?? '';
    $stmt = $pdo->prepare("SELECT * FROM users WHERE email = ? AND status = 'active'");
    $stmt->execute([$email]);
    $user = $stmt->fetch(PDO::FETCH_ASSOC);
    if ($user && $user['password'] === hash('sha256', $password)) {
        $_SESSION['user_id'] = $user['id'];
        $_SESSION['role'] = $user['role'];
        $_SESSION['team'] = $user['team'];
        echo json_encode(['success' => true, 'user' => [
            'id' => $user['id'], 'name' => $user['name'],
            'email' => $user['email'], 'role' => $user['role'],
            'team' => $user['team'], 'level' => $user['level'],
            'value' => $user['value'], 'status' => $user['status']
        ]]);
    } else {
        echo json_encode(['success' => false, 'message' => 'Email ou mot de passe incorrect']);
    }
}

if ($action === 'register') {
    $name = trim($_POST['name'] ?? '');
    $email = trim($_POST['email'] ?? '');
    $phone = $_POST['phone'] ?? '';
    $dob = $_POST['dob'] ?? '';
    $height = $_POST['height'] ?? '';
    $nationality = $_POST['nationality'] ?? 'Togolaise';
    $team = $_POST['team'] ?? '';
    $position = $_POST['position'] ?? '';
    $number = $_POST['number'] ?? null;
    $password = $_POST['password'] ?? '';

    if (!$name || !$email || !$password || !$team) {
        echo json_encode(['success' => false, 'message' => 'Champs manquants']);
        exit;
    }

    $stmt = $pdo->prepare("SELECT id FROM users WHERE email = ?");
    $stmt->execute([$email]);
    if ($stmt->fetch()) {
        echo json_encode(['success' => false, 'message' => 'Email deja utilise']);
        exit;
    }

    $id = uniqid('player_');
    $hashedPassword = hash('sha256', $password);

    $stmt = $pdo->prepare("INSERT INTO users (id,name,email,password,role,team,position,number,height,nationality,status) VALUES (?,?,?,?,'player',?,?,?,?,?,'pending')");
    $stmt->execute([$id,$name,$email,$hashedPassword,$team,$position,$number,$height,$nationality]);

    $stmt2 = $pdo->prepare("INSERT INTO registrations (name,email,phone,dob,team,position,number,height) VALUES (?,?,?,?,?,?,?,?)");
    $stmt2->execute([$name,$email,$phone,$dob,$team,$position,$number,$height]);

    echo json_encode(['success' => true, 'message' => 'Demande envoyee avec succes']);
}

if ($action === 'logout') {
    session_destroy();
    echo json_encode(['success' => true]);
}
?>
