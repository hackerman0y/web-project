<?php
// Skill.php - Hala
// handles all skill operations (add, edit, delete, get)

require_once 'Config.php';
require_once 'Auth.php';

header('Content-Type: application/json');
header('Access-Control-Allow-Origin: *');
header('Access-Control-Allow-Methods: GET, POST, PUT, DELETE, OPTIONS');
header('Access-Control-Allow-Headers: Content-Type');

if ($_SERVER['REQUEST_METHOD'] === 'OPTIONS') exit;

Auth::start();

$pdo    = Config::getDB();
$method = $_SERVER['REQUEST_METHOD'];
$id     = isset($_GET['id']) ? (int)$_GET['id'] : null;
$body   = json_decode(file_get_contents('php://input'), true) ?? [];


// add new skill
if ($method === 'POST') {
    Auth::require();

    $name     = trim($body['name'] ?? '');
    $category = trim($body['category'] ?? '');
    $type     = trim($body['type'] ?? '');
    $desc     = trim($body['description'] ?? '');
    $teach    = trim($body['skillToTeach'] ?? $body['skill_to_teach'] ?? '');
    $learn    = trim($body['skillToLearn'] ?? $body['skill_to_learn'] ?? '');

    if (!$name) Auth::json(['error' => 'skill name is required'], 400);

    $stmt = $pdo->prepare(
        'INSERT INTO skills (user_id, name, category, type, description, popularity, skill_to_teach, skill_to_learn)
         VALUES (?, ?, ?, ?, ?, 0, ?, ?)'
    );
    $stmt->execute([Auth::id(), $name, $category, $type, $desc, $teach, $learn]);

    $newId = (int) $pdo->lastInsertId();
    Auth::json(getSkill($pdo, $newId), 201);
}


// update skill or increase popularity
if ($method === 'PUT' && $id) {
    Auth::require();

    // just increase the likes
    if (isset($_GET['increase'])) {
        $pdo->prepare('UPDATE skills SET popularity = popularity + 1 WHERE id = ?')->execute([$id]);
        Auth::json(getSkill($pdo, $id));
    }

    $skill = getSkill($pdo, $id);
    if (!$skill) Auth::json(['error' => 'skill not found'], 404);

    // make sure it's the owner
    if ((int)$skill['user_id'] !== Auth::id()) Auth::json(['error' => 'not your skill'], 403);

    $name     = $body['name']         ?? $skill['name'];
    $category = $body['category']     ?? $skill['category'];
    $type     = $body['type']         ?? $skill['type'];
    $desc     = $body['description']  ?? $skill['description'];
    $teach    = $body['skillToTeach'] ?? $body['skill_to_teach'] ?? $skill['skill_to_teach'];
    $learn    = $body['skillToLearn'] ?? $body['skill_to_learn'] ?? $skill['skill_to_learn'];

    $pdo->prepare(
        'UPDATE skills SET name=?, category=?, type=?, description=?, skill_to_teach=?, skill_to_learn=? WHERE id=?'
    )->execute([$name, $category, $type, $desc, $teach, $learn, $id]);

    Auth::json(getSkill($pdo, $id));
}


// delete skill
if ($method === 'DELETE' && $id) {
    Auth::require();

    $skill = getSkill($pdo, $id);
    if (!$skill) Auth::json(['error' => 'skill not found'], 404);
    if ((int)$skill['user_id'] !== Auth::id()) Auth::json(['error' => 'not your skill'], 403);

    $pdo->prepare('DELETE FROM skills WHERE id = ?')->execute([$id]);
    Auth::json(['message' => 'deleted']);
}


// get one skill
if ($method === 'GET' && $id) {
    $skill = getSkill($pdo, $id);
    if (!$skill) Auth::json(['error' => 'not found'], 404);
    Auth::json($skill);
}

// get skills for a specific user
if ($method === 'GET' && isset($_GET['user_id'])) {
    $userId = (int)$_GET['user_id'];
    $stmt = $pdo->prepare(
        'SELECT s.*, u.username FROM skills s
         JOIN users u ON u.id = s.user_id
         WHERE s.user_id = ? ORDER BY s.id DESC'
    );
    $stmt->execute([$userId]);
    Auth::json($stmt->fetchAll());
}

// get all skills
if ($method === 'GET') {
    $stmt = $pdo->query(
        'SELECT s.*, u.username FROM skills s
         JOIN users u ON u.id = s.user_id
         ORDER BY s.popularity DESC, s.id DESC'
    );
    Auth::json($stmt->fetchAll());
}

Auth::json(['error' => 'something went wrong'], 405);


// helper - fetch a skill with the username joined
function getSkill($pdo, $id) {
    $stmt = $pdo->prepare(
        'SELECT s.*, u.username FROM skills s
         JOIN users u ON u.id = s.user_id
         WHERE s.id = ?'
    );
    $stmt->execute([$id]);
    return $stmt->fetch();
}
