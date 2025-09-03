<?php
require_once __DIR__ . '/../config/cors.php';
require_once __DIR__ . '/../config/db.php';
require_once __DIR__ . '/../utils/jwt.php';

header('Content-Type: application/json; charset=utf-8');

function input(): array {
  $ct = $_SERVER['CONTENT_TYPE'] ?? '';
  if (stripos($ct, 'application/json') !== false) {
    $raw = file_get_contents('php://input');
    $d = json_decode($raw, true);
    if (json_last_error() === JSON_ERROR_NONE) return $d ?: [];
  }
  return $_POST ?: [];
}

if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
  http_response_code(405);
  echo json_encode(['error' => 'Method not allowed']); exit;
}

$d = input();
$email = trim((string)($d['email'] ?? ''));
$password = (string)($d['password'] ?? '');

if ($email === '' || $password === '') {
  http_response_code(400);
  echo json_encode(['error' => 'email and password are required']); exit;
}

try {
  $pdo = db();
  $stmt = $pdo->prepare("SELECT * FROM users WHERE email = :email LIMIT 1");
  $stmt->execute([':email' => $email]);
  $user = $stmt->fetch();

  if (!$user || (int)$user['is_active'] !== 1) {
    http_response_code(401);
    echo json_encode(['error' => 'Invalid credentials']); exit;
  }

  if (!password_verify($password, $user['password_hash'])) {
    http_response_code(401);
    echo json_encode(['error' => 'Invalid credentials']); exit;
  }

  // Update last_login_at
  $pdo->prepare("UPDATE users SET last_login_at = NOW() WHERE id = :id")->execute([':id' => (int)$user['id']]);

  $secret = $_ENV['JWT_SECRET'] ?? '';
  $ttl = (int)($_ENV['JWT_TTL'] ?? 3600);
  $refreshTtl = (int)($_ENV['REFRESH_TTL'] ?? 604800);

  $payload = [
    'uid' => (int)$user['id'],
    'role' => $user['role'],
    'typ' => 'at' // access token
  ];
  $access = jwt_encode($payload, $secret, $ttl);

  $refresh = jwt_encode([
    'uid' => (int)$user['id'],
    'role' => $user['role'],
    'typ' => 'rt' // refresh token
  ], $secret, $refreshTtl);

  // Return stripped user
  $public = [
    'id' => (int)$user['id'],
    'full_name' => $user['full_name'],
    'email' => $user['email'],
    'phone' => $user['phone'],
    'role' => $user['role'],
    'is_active' => (int)$user['is_active'],
    'last_login_at' => $user['last_login_at'],
    'created_at' => $user['created_at'],
    'updated_at' => $user['updated_at']
  ];

  echo json_encode([
    'success' => true,
    'user' => $public,
    'access_token' => $access,
    'expires_in' => $ttl,
    'refresh_token' => $refresh,
    'refresh_expires_in' => $refreshTtl
  ]);

} catch (Throwable $t) {
  http_response_code(500);
  echo json_encode(['error' => 'Server error']);
}