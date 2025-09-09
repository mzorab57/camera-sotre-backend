<?php
// auth/refresh.php
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
$refresh = trim((string)($d['refresh_token'] ?? ''));

if ($refresh === '') {
  http_response_code(400);
  echo json_encode(['error' => 'refresh_token is required']); exit;
}

try {
  $secret = $_ENV['JWT_SECRET'] ?? '';
  $payload = jwt_decode($refresh, $secret);
  if (($payload['typ'] ?? '') !== 'rt') throw new Exception('Invalid token type');

  // verify user still active
  $pdo = db();
  $stmt = $pdo->prepare("SELECT id, role, is_active FROM users WHERE id = :id");
  $stmt->execute([':id' => (int)$payload['uid']]);
  $user = $stmt->fetch();
  if (!$user || (int)$user['is_active'] !== 1) {
    http_response_code(401);
    echo json_encode(['error' => 'Account disabled or not found']); exit;
  }

  $ttl = (int)($_ENV['JWT_TTL'] ?? 3600);
  $access = jwt_encode([
    'uid' => (int)$user['id'],
    'role' => $user['role'],
    'typ' => 'at'
  ], $secret, $ttl);

  echo json_encode([
    'success' => true,
    'access_token' => $access,
    'expires_in' => $ttl
  ]);

} catch (Throwable $t) {
  http_response_code(401);
  echo json_encode(['error' => 'Invalid refresh_token']);
}