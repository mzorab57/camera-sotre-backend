<?php
// middleware/require_role.php
require_once __DIR__ . '/require_auth.php';

function require_role(array $roles): void {
  $user = auth_user();
  if (!$user) {
    require_auth();
    $user = auth_user();
  }
  $role = $user['role'] ?? null;
  if (!$role || !in_array($role, $roles, true)) {
    http_response_code(403);
    echo json_encode(['error' => 'Forbidden: insufficient role']); exit;
  }
}