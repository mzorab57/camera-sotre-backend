<?php
require_once __DIR__ . '/../config/cors.php';
require_once __DIR__ . '/../config/db.php';

header('Content-Type: application/json; charset=utf-8');

if ($_SERVER['REQUEST_METHOD'] !== 'GET') {
  http_response_code(405); echo json_encode(['error'=>'Method not allowed']); exit;
}
$pdo = db();

if (isset($_GET['id'])) {
  $id = (int)$_GET['id'];
  $s = $pdo->prepare("SELECT * FROM product_specifications WHERE id = :id");
  $s->execute([':id'=>$id]);
  $row = $s->fetch();
  if (!$row) { http_response_code(404); echo json_encode(['error'=>'Not found']); exit; }
  echo json_encode(['success'=>true,'data'=>$row]); exit;
}

$product_id = isset($_GET['product_id']) ? (int)$_GET['product_id'] : 0;
if ($product_id <= 0) { http_response_code(400); echo json_encode(['error'=>'product_id is required']); exit; }

$where = ['product_id = :pid'];
$params = [':pid'=>$product_id];

if (!empty($_GET['spec_group'])) {
  $where[] = 'spec_group = :grp';
  $params[':grp'] = trim($_GET['spec_group']);
}

$w = 'WHERE ' . implode(' AND ', $where);
$sql = "SELECT * FROM product_specifications $w ORDER BY spec_group IS NULL, spec_group, display_order, spec_name";
$s = $pdo->prepare($sql);
$s->execute($params);
$rows = $s->fetchAll();

if (!empty($_GET['grouped'])) {
  $grouped = [];
  foreach ($rows as $r) {
    $g = $r['spec_group'] ?? '';
    if (!isset($grouped[$g])) $grouped[$g] = [];
    $grouped[$g][] = $r;
  }
  echo json_encode(['success'=>true,'data'=>$grouped]); exit;
}

echo json_encode(['success'=>true,'data'=>$rows]);