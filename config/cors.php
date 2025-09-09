<?php
$origin = $_SERVER['HTTP_ORIGIN'] ?? '';
$allowed = $_ENV['ALLOWED_ORIGINS'] ?? '*';
$allowAll = trim($allowed) === '*';
$allowedList = array_map('trim', explode(',', $allowed));

if ($allowAll) {
  header('Access-Control-Allow-Origin: *');
} elseif ($origin && in_array($origin, $allowedList, true)) {
  header("Access-Control-Allow-Origin: $origin");
  header('Vary: Origin');
}
header('Access-Control-Allow-Methods: GET, POST, PUT, PATCH, DELETE, OPTIONS');
header('Access-Control-Allow-Headers: Content-Type, Authorization, X-Requested-With');
header('Access-Control-Max-Age: 86400');
header('Content-Type: application/json; charset=utf-8');
if (isset($_SERVER['REQUEST_METHOD']) && $_SERVER['REQUEST_METHOD'] === 'OPTIONS') { http_response_code(204); exit; }