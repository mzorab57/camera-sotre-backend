<?php
require_once __DIR__ . '/../config/cors.php';
require_once __DIR__ . '/../config/db.php';
require_once __DIR__ . '/../middleware/protect_admin.php';

if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
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

$data = input();

$full_name = trim($data['full_name'] ?? '');
$email     = trim($data['email'] ?? '');
$password  = $data['password'] ?? null;
$phone     = isset($data['phone']) ? trim((string)$data['phone']) : null;
$role      = $data['role'] ?? 'employee';
$is_active = isset($data['is_active']) ? (int)!!$data['is_active'] : 1;

if ($full_name === '' || $email === '' || !$password) {
    http_response_code(400);
    echo json_encode(['error' => 'full_name, email, password are required']);
    exit;
}
if (!filter_var($email, FILTER_VALIDATE_EMAIL)) {
    http_response_code(422);
    echo json_encode(['error' => 'Invalid email']);
    exit;
}
if (!in_array($role, ['admin','employee'], true)) {
    http_response_code(422);
    echo json_encode(['error' => 'Invalid role']);
    exit;
}

$hash = password_hash($password, PASSWORD_DEFAULT);

$sql = "INSERT INTO users (full_name, email, phone, password_hash, role, is_active)
        VALUES (:full_name, :email, :phone, :password_hash, :role, :is_active)";

try {
    $pdo = db();
    $stmt = $pdo->prepare($sql);
    $stmt->execute([
        ':full_name'     => $full_name,
        ':email'         => $email,
        ':phone'         => $phone ?: null,
        ':password_hash' => $hash,
        ':role'          => $role,
        ':is_active'     => $is_active,
    ]);

    $id = (int)$pdo->lastInsertId();
    $get = $pdo->prepare("SELECT id, full_name, email, phone, role, is_active, last_login_at, created_at, updated_at FROM users WHERE id = :id");
    $get->execute([':id' => $id]);
    $user = $get->fetch();

    http_response_code(201);
    echo json_encode(['success' => true, 'data' => $user]);

} catch (PDOException $e) {
    if ($e->getCode() === '23000') {
        http_response_code(409);
        echo json_encode(['error' => 'Duplicate email or phone']);
        exit;
    }
    http_response_code(500);
    echo json_encode(['error' => 'Database error']);
}