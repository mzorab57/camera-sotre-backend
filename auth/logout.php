<?php
require_once __DIR__ . '/../config/cors.php';
header('Content-Type: application/json; charset=utf-8');

/*
  Stateless JWT: Logout تەنیا لە کڵایەنتەوە بە سڕینەوەی token ـەکان.
  ئەگەر blacklist/token store دەتەوێت، پێویستە بە دیتابەیس زیاد بکەیت.
*/
if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
  http_response_code(405); echo json_encode(['error'=>'Method not allowed']); exit;
}
echo json_encode(['success'=>true, 'message'=>'Logged out (client should discard tokens)']);