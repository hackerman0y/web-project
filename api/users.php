<?php
error_reporting(0);
ini_set('display_errors', 0);

require __DIR__ . '/../config.php';

header("Content-Type: application/json");

$action = $_GET['action'] ?? '';
$data = json_decode(file_get_contents("php://input"), true);

// LOGIN
if ($action === 'login') {
    $email = $data['email'] ?? '';
    $pass  = $data['password'] ?? '';

    $stmt = $pdo->prepare("SELECT * FROM users WHERE email = ?");
    $stmt->execute([$email]);
    $user = $stmt->fetch();

    if ($user && password_verify($pass, $user['password'])) {
        $token = bin2hex(random_bytes(32));
        $stmt2 = $pdo->prepare("UPDATE users SET token = ? WHERE id = ?");
        $stmt2->execute([$token, $user['id']]);

        echo json_encode([
            'success'  => true,
            'token'    => $token,
            'userId'   => $user['id'],
            'username' => $user['name']
        ]);
    } else {
        http_response_code(401);
        echo json_encode(['success' => false, 'message' => 'Invalid credentials']);
    }
}

// REGISTER
elseif ($action === 'register') {
    $name  = $data['username'] ?? '';
    $email = $data['email'] ?? '';
    $pass  = $data['password'] ?? '';

    if (!$name || !$email || !$pass) {
        http_response_code(400);
        echo json_encode(['success' => false]);
        exit;
    }

    $hashed = password_hash($pass, PASSWORD_DEFAULT);
    $stmt = $pdo->prepare("INSERT INTO users (name, email, password) VALUES (?, ?, ?)");
    $stmt->execute([$name, $email, $hashed]);

    echo json_encode(['success' => true]);
}

// LIST USERS
elseif ($action === 'list') {
    $auth = $_SERVER['HTTP_AUTHORIZATION'] ?? '';
    $token = str_replace('Bearer ', '', $auth);

    $stmt = $pdo->prepare("SELECT id FROM users WHERE token = ?");
    $stmt->execute([$token]);
    $currentUser = $stmt->fetch();

    if (!$currentUser) {
        http_response_code(401);
        echo json_encode(['error' => 'Unauthorized']);
        exit;
    }

    $stmt = $pdo->prepare("SELECT id, name as username FROM users WHERE id != ?");
    $stmt->execute([$currentUser['id']]);
    $users = $stmt->fetchAll(PDO::FETCH_ASSOC);

    echo json_encode($users);
}

// DEFAULT
else {
    echo json_encode(['success' => false, 'message' => 'Invalid route']);
}