<?php
// middleware/require_auth.php
require_once __DIR__ . '/../config/cors.php';
require_once __DIR__ . '/../config/db.php';
require_once __DIR__ . '/../utils/jwt.php';

$GLOBALS['AUTH_USER'] = null;
$GLOBALS['AUTH_PAYLOAD'] = null;

function get_authorization_token(): ?string {
  $hdr = $_SERVER['HTTP_AUTHORIZATION'] ?? ($_SERVER['REDIRECT_HTTP_AUTHORIZATION'] ?? '');
  if (!$hdr && function_exists('apache_request_headers')) {
    $h = apache_request_headers();
    if (isset($h['Authorization'])) $hdr = $h['Authorization'];
  }
  if (!$hdr) return null;
  if (stripos($hdr, 'Bearer ') === 0) return trim(substr($hdr, 7));
  return null;
}

function require_auth(): array {
  $token = get_authorization_token();
  if (!$token) {
    http_response_code(401);
    echo json_encode(['error' => 'Missing Authorization Bearer token']); exit;
  }
  try {
    $secret = $_ENV['JWT_SECRET'] ?? '';
    if ($secret === '') throw new Exception('JWT secret missing');
    $payload = jwt_decode($token, $secret);

    // Fetch user and validate active
    $pdo = db();
    $stmt = $pdo->prepare("SELECT id, full_name, email, phone, role, is_active, last_login_at, created_at, updated_at 
                           FROM users WHERE id = :id");
    $stmt->execute([':id' => (int)($payload['uid'] ?? 0)]);
    $user = $stmt->fetch();
    if (!$user || (int)$user['is_active'] !== 1) {
      http_response_code(401);
      echo json_encode(['error' => 'Account disabled or not found']); exit;
    }

    $GLOBALS['AUTH_USER'] = $user;
    $GLOBALS['AUTH_PAYLOAD'] = $payload;
    return $payload;

  } catch (Throwable $t) {
    http_response_code(401);
    echo json_encode(['error' => 'Invalid token']); exit;
  }
}

function auth_user(): ?array {
  return $GLOBALS['AUTH_USER'] ?? null;
}

function auth_payload(): ?array {
  return $GLOBALS['AUTH_PAYLOAD'] ?? null;
}