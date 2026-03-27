<?php
$origin = $_SERVER['HTTP_ORIGIN'] ?? '';

// Prefer ALLOWED_ORIGINS from environment if available, fallback to hardcoded list
$envAllowed = getenv('ALLOWED_ORIGINS');
$baseDefaults = [
  'https://adnanshops.com',
  'https://www.adnanshops.com',
  'https://dashboard.adnanshops.com',
  // ***** Bo local amana ba kar bena *****
   'http://localhost:5173',
   'http://localhost:5174'
];
$allowedList = $baseDefaults;
if ($envAllowed && trim($envAllowed) !== '') {
  $fromEnv = array_values(array_filter(array_map('trim', explode(',', $envAllowed))));
  $allowedList = array_values(array_unique(array_merge($fromEnv, $baseDefaults)));
}

if ($origin) {
  if (in_array('*', $allowedList, true)) {
    header("Access-Control-Allow-Origin: *");
  } elseif (in_array($origin, $allowedList, true)) {
    header("Access-Control-Allow-Origin: $origin");
    header('Vary: Origin');
  }
}

header('Access-Control-Allow-Methods: GET, POST, PUT, PATCH, DELETE, OPTIONS');
header('Access-Control-Allow-Headers: Content-Type, Authorization, X-Requested-With');
header('Access-Control-Max-Age: 86400');


if (($_SERVER['REQUEST_METHOD'] ?? '') === 'OPTIONS') {
  http_response_code(204);
  exit;
}
