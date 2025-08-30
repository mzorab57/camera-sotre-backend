<?php
require_once __DIR__ . '/../config/cors.php';
require_once __DIR__ . '/../config/db.php';
header('Content-Type: application/json; charset=utf-8');

function input(): array {
  $ct = $_SERVER['CONTENT_TYPE'] ?? '';
  if (stripos($ct, 'application/json') !== false) {
    $raw = file_get_contents('php://input'); $d = json_decode($raw, true);
    if (json_last_error() === JSON_ERROR_NONE) return $d ?: [];
  }
  return $_POST ?: [];
}
if (!function_exists('slugify')) {
  function slugify($t){ $t=trim($t); $t=preg_replace('~[^\pL\d]+~u','-',$t); $t=trim($t,'-'); $t=strtolower($t); $t=preg_replace('~[^-\w]+~','',$t); return $t?:'tag'; }
}

if ($_SERVER['REQUEST_METHOD'] !== 'POST') { http_response_code(405); echo json_encode(['error'=>'Method not allowed']); exit; }

$d = input();
$name = trim($d['name'] ?? '');
$slug = trim($d['slug'] ?? '');

if ($name === '') { http_response_code(400); echo json_encode(['error'=>'name is required']); exit; }
if ($slug === '') $slug = slugify($name);

try {
  $pdo = db();
  $ins = $pdo->prepare("INSERT INTO tags (name, slug) VALUES (:name, :slug)");
  $ins->execute([':name'=>$name, ':slug'=>$slug]);

  $id = (int)$pdo->lastInsertId();
  $g = $pdo->prepare("SELECT * FROM tags WHERE id=:id");
  $g->execute([':id'=>$id]);
  echo json_encode(['success'=>true,'data'=>$g->fetch()], JSON_UNESCAPED_UNICODE);

} catch (PDOException $e) {
  if ($e->getCode()==='23000'){ http_response_code(409); echo json_encode(['error'=>'Duplicate name or slug']); exit; }
  http_response_code(500); echo json_encode(['error'=>'Database error']);
}