<?php
error_reporting(0);
ini_set('display_errors', 0);

require __DIR__ . '/../config.php';
require __DIR__ . '/../auth.php';

header("Content-Type: application/json");
header("Access-Control-Allow-Origin: *");
header("Access-Control-Allow-Headers: Authorization, Content-Type");

if ($_SERVER['REQUEST_METHOD'] === 'OPTIONS') {
    exit;
}

$user = getAuthUser($pdo);
$action = $_GET['action'] ?? '';

if ($action === 'send') {
    $data = json_decode(file_get_contents("php://input"), true);
    $to  = $data['to_user_id'] ?? null;
    $msg = trim($data['message'] ?? '');

    if (!$to || !$msg) {
        echo json_encode(['success' => false, 'error' => 'Missing data']);
        exit;
    }

    $stmt = $pdo->prepare("INSERT INTO messages (from_user_id, to_user_id, message, seen) VALUES (?, ?, ?, 0)");
    $stmt->execute([$user['id'], $to, $msg]);

    echo json_encode(['success' => true, 'id' => $pdo->lastInsertId()]);

} elseif ($action === 'history') {
    $other = $_GET['with'] ?? null;

    if (!$other) {
        echo json_encode([]);
        exit;
    }

    $stmt = $pdo->prepare("
        SELECT id, from_user_id, to_user_id, message, seen, created_at
        FROM messages 
        WHERE (from_user_id = ? AND to_user_id = ?)
           OR (from_user_id = ? AND to_user_id = ?)
        ORDER BY id ASC
    ");
    $stmt->execute([$user['id'], $other, $other, $user['id']]);

    echo json_encode($stmt->fetchAll(PDO::FETCH_ASSOC));

} elseif ($action === 'seen') {
    $other = $_GET['with'] ?? null;

    if (!$other) {
        echo json_encode(['success' => false]);
        exit;
    }

    $stmt = $pdo->prepare("UPDATE messages SET seen = 1 WHERE from_user_id = ? AND to_user_id = ? AND seen = 0");
    $stmt->execute([$other, $user['id']]);

    echo json_encode(['success' => true]);

} else {
    echo json_encode(['error' => 'Invalid action']);
}