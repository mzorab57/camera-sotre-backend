<?php
// auth/me.php
require_once __DIR__ . '/../middleware/require_auth.php';

require_auth();
$user = auth_user();
echo json_encode(['success' => true, 'user' => $user]);