<?php
require_once __DIR__ . '/../config/cors.php';
require_once __DIR__ . '/../config/db.php';
require_once __DIR__ . '/../middleware/protect_admin.php';

if (!in_array($_SERVER['REQUEST_METHOD'], ['POST','DELETE'], true)) {
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

$restore = isset($_GET['restore']) ? (int)$_GET['restore'] : (int)($input['restore'] ?? 0);
$newStatus = $restore ? 1 : 0;

try {
    $pdo = db();

    $stmt = $pdo->prepare("UPDATE users SET is_active = :status WHERE id = :id");
    $stmt->execute([':status' => $newStatus, ':id' => $id]);

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

    echo json_encode([
        'success' => true,
        'message' => $restore ? 'User restored (is_active=1)' : 'User deactivated (is_active=0)',
        'data' => $user
    ]);

} catch (PDOException $e) {
    http_response_code(500);
    echo json_encode(['error' => 'Database error']);
}