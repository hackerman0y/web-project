<?php
// User_list.php - Hala
// get all users or a single user by id

require_once 'Config.php';
require_once 'Auth.php';

header('Content-Type: application/json');
header('Access-Control-Allow-Origin: *');
header('Access-Control-Allow-Methods: GET, PUT, OPTIONS');
header('Access-Control-Allow-Headers: Content-Type');

if ($_SERVER['REQUEST_METHOD'] === 'OPTIONS') exit;

Auth::start();

$pdo = Config::getDB();
$method = $_SERVER['REQUEST_METHOD'];

// update profile
if ($method === 'PUT' && isset($_GET['update'])) {
    Auth::require();

    $body = json_decode(file_get_contents('php://input'), true);
    $username = trim($body['username'] ?? '');
    $email    = trim($body['email'] ?? '');

    if (!$username && !$email) {
        Auth::json(['error' => 'nothing to update'], 400);
    }

    $sets = [];
    $params = [];

    if ($username) {
        $sets[] = 'username = ?';
        $params[] = $username;
    }
    if ($email) {
        $sets[] = 'email = ?';
        $params[] = $email;
    }

    $params[] = Auth::id();
    $pdo->prepare('UPDATE users SET ' . implode(', ', $sets) . ' WHERE id = ?')->execute($params);

    $stmt = $pdo->prepare('SELECT id, username, email, online, trust_score, coins FROM users WHERE id = ?');
    $stmt->execute([Auth::id()]);
    Auth::json($stmt->fetch());
}

// get currently logged in user
if ($method === 'GET' && isset($_GET['me'])) {
    Auth::require();

    $stmt = $pdo->prepare('SELECT id, username, email, online, trust_score, coins FROM users WHERE id = ?');
    $stmt->execute([Auth::id()]);
    $user = $stmt->fetch();

    if (!$user) Auth::json(['error' => 'not found'], 404);

    Auth::json($user);
}

// get one user by id
if ($method === 'GET' && isset($_GET['id'])) {
    $id = (int) $_GET['id'];

    $stmt = $pdo->prepare('SELECT id, username, email, online, trust_score, coins FROM users WHERE id = ?');
    $stmt->execute([$id]);
    $user = $stmt->fetch();

    if (!$user) Auth::json(['error' => 'user not found'], 404);

    Auth::json($user);
}

// get all users
if ($method === 'GET') {
    $stmt = $pdo->query('SELECT id, username, email, online, trust_score, coins FROM users ORDER BY id');
    Auth::json($stmt->fetchAll());
}

Auth::json(['error' => 'method not allowed'], 405);
