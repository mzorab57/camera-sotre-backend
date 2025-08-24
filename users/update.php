<?php
require_once __DIR__ . '/../config/cors.php';
require_once __DIR__ . '/../config/db.php';

if (!in_array($_SERVER['REQUEST_METHOD'], ['POST','PUT','PATCH'], true)) {
    http_response_code(405);
    echo json_encode(['error' => 'Method not allowed']);
    exit;
}

function input() {
    $ct = $_SERVER['CONTENT_TYPE'] ?? '';
    if (stripos($ct, 'application/json') !== false) {
        $raw = file_get_contents('php://input');
        $data = json_decode($raw, true);
        if (json_last_error() === JSON_ERROR_NONE) return $data ?: [];
    }
    return $_POST ?: [];
}

$input = input();
$id = isset($_GET['id']) ? (int)$_GET['id'] : (int)($input['id'] ?? 0);
if ($id <= 0) {
    http_response_code(400);
    echo json_encode(['error' => 'id is required']);
    exit;
}

if (isset($input['email'])) {
    http_response_code(400);
    echo json_encode(['error' => 'Email cannot be changed']);
    exit;
}

$fields = [];
$params = [':id' => $id];

if (isset($input['full_name'])) {
    $name = trim($input['full_name']);
    if ($name === '') {
        http_response_code(422);
        echo json_encode(['error' => 'full_name cannot be empty']);
        exit;
    }
    $fields[] = 'full_name = :full_name';
    $params[':full_name'] = $name;
}

if (array_key_exists('phone', $input)) {
    $phone = trim((string)$input['phone']);
    $fields[] = 'phone = :phone';
    $params[':phone'] = ($phone !== '') ? $phone : null;
}

if (isset($input['role'])) {
    if (!in_array($input['role'], ['admin','employee'], true)) {
        http_response_code(422);
        echo json_encode(['error' => 'Invalid role']);
        exit;
    }
    $fields[] = 'role = :role';
    $params[':role'] = $input['role'];
}

if (isset($input['is_active'])) {
    $fields[] = 'is_active = :is_active';
    $params[':is_active'] = (int)!!$input['is_active'];
}

if (!empty($input['password'])) {
    $fields[] = 'password_hash = :password_hash';
    $params[':password_hash'] = password_hash($input['password'], PASSWORD_DEFAULT);
}

if (!$fields) {
    http_response_code(400);
    echo json_encode(['error' => 'No fields to update']);
    exit;
}

$sql = "UPDATE users SET " . implode(', ', $fields) . " WHERE id = :id";

try {
    $pdo = db();
    $stmt = $pdo->prepare($sql);
    $stmt->execute($params);

    if ($stmt->rowCount() === 0) {
        $check = $pdo->prepare("SELECT id FROM users WHERE id = :id");
        $check->execute([':id' => $id]);
        if (!$check->fetch()) {
            http_response_code(404);
            echo json_encode(['error' => 'User not found']);
            exit;
        }
    }

    $get = $pdo->prepare("SELECT id, full_name, email, phone, role, is_active, last_login_at, created_at, updated_at FROM users WHERE id = :id");
    $get->execute([':id' => $id]);
    $user = $get->fetch();

    echo json_encode(['success' => true, 'data' => $user]);

} catch (PDOException $e) {
    if ($e->getCode() === '23000') {
        http_response_code(409);
        echo json_encode(['error' => 'Duplicate phone']);
        exit;
    }
    http_response_code(500);
    echo json_encode(['error' => 'Database error']);
}