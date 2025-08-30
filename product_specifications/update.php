<?php
require_once __DIR__ . '/../config/cors.php';
require_once __DIR__ . '/../config/db.php';

header('Content-Type: application/json; charset=utf-8');

function input(): array {
  $ct = $_SERVER['CONTENT_TYPE'] ?? '';
  if (stripos($ct,'application/json')!==false) {
    $raw=file_get_contents('php://input'); $d=json_decode($raw,true);
    if (json_last_error()===JSON_ERROR_NONE) return $d?:[];
  }
  return $_POST ?: [];
}

if (!in_array($_SERVER['REQUEST_METHOD'] ?? 'GET', ['POST','PUT','PATCH'], true)) {
  http_response_code(405); echo json_encode(['error'=>'Method not allowed']); exit;
}

$pdo = db();
$d = input();
$id = isset($_GET['id']) ? (int)$_GET['id'] : (int)($d['id'] ?? 0);
if ($id <= 0) { http_response_code(400); echo json_encode(['error'=>'id is required']); exit; }

$cur = $pdo->prepare("SELECT * FROM product_specifications WHERE id = :id");
$cur->execute([':id'=>$id]);
$row = $cur->fetch();
if (!$row) { http_response_code(404); echo json_encode(['error'=>'Spec not found']); exit; }

$fields = []; $p = [':id'=>$id];

// Move to another product (optional)
if (array_key_exists('product_id', $d)) {
  $pid = (int)$d['product_id'];
  if ($pid <= 0) { http_response_code(422); echo json_encode(['error'=>'Invalid product_id']); exit; }
  $chk = $pdo->prepare("SELECT id FROM products WHERE id = :id");
  $chk->execute([':id'=>$pid]);
  if (!$chk->fetch()) { http_response_code(422); echo json_encode(['error'=>'product_id not found']); exit; }
  $fields[] = 'product_id = :pid'; $p[':pid'] = $pid;
}

if (array_key_exists('spec_name', $d)) {
  $name = trim((string)$d['spec_name']);
  if ($name === '') { http_response_code(422); echo json_encode(['error'=>'spec_name cannot be empty']); exit; }
  $fields[] = 'spec_name = :name'; $p[':name'] = $name;
}

if (array_key_exists('spec_value', $d)) {
  $val = (string)$d['spec_value'];
  if ($val === '') { http_response_code(422); echo json_encode(['error'=>'spec_value cannot be empty']); exit; }
  $fields[] = 'spec_value = :val'; $p[':val'] = $val;
}

if (array_key_exists('spec_group', $d)) {
  $grp = trim((string)$d['spec_group']);
  $fields[] = 'spec_group = :grp'; $p[':grp'] = ($grp !== '' ? $grp : null);
}

if (array_key_exists('display_order', $d)) {
  $fields[] = 'display_order = :ord'; $p[':ord'] = (int)$d['display_order'];
}

if (!$fields) { http_response_code(400); echo json_encode(['error'=>'No fields to update']); exit; }

$u = $pdo->prepare("UPDATE product_specifications SET ".implode(', ', $fields)." WHERE id = :id");
$u->execute($p);

$g = $pdo->prepare("SELECT * FROM product_specifications WHERE id = :id");
$g->execute([':id'=>$id]);
echo json_encode(['success'=>true,'data'=>$g->fetch()]);