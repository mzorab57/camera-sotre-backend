<?php
// utils/jwt.php

function b64url_encode(string $data): string {
  return rtrim(strtr(base64_encode($data), '+/', '-_'), '=');
}

function b64url_decode(string $data): string {
  $remainder = strlen($data) % 4;
  if ($remainder) $data .= str_repeat('=', 4 - $remainder);
  return base64_decode(strtr($data, '-_', '+/'));
}

function jwt_encode(array $payload, string $secret, int $ttl = 3600, array $header = []): string {
  $header = array_merge(['alg' => 'HS256', 'typ' => 'JWT'], $header);
  $now = time();
  if (!isset($payload['iat'])) $payload['iat'] = $now;
  if (!isset($payload['exp'])) $payload['exp'] = $now + $ttl;

  $h = b64url_encode(json_encode($header, JSON_UNESCAPED_UNICODE));
  $p = b64url_encode(json_encode($payload, JSON_UNESCAPED_UNICODE));
  $sig = hash_hmac('sha256', $h . '.' . $p, $secret, true);
  $s = b64url_encode($sig);

  return $h . '.' . $p . '.' . $s;
}

function jwt_decode(string $token, string $secret): array {
  $parts = explode('.', $token);
  if (count($parts) !== 3) throw new Exception('Invalid token format');
  [$h, $p, $s] = $parts;

  $header = json_decode(b64url_decode($h), true);
  $payload = json_decode(b64url_decode($p), true);
  if (!is_array($header) || !is_array($payload)) throw new Exception('Invalid token encoding');
  if (($header['alg'] ?? '') !== 'HS256') throw new Exception('Unsupported alg');

  $calc = b64url_encode(hash_hmac('sha256', $h . '.' . $p, $secret, true));
  if (!hash_equals($calc, $s)) throw new Exception('Invalid signature');

  if (isset($payload['exp']) && time() >= (int)$payload['exp']) throw new Exception('Token expired');
  return $payload;
}