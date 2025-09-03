<?php
require_once __DIR__ . '/../config/cors.php';
require_once __DIR__ . '/../config/db.php';
require_once __DIR__ . '/../middleware/protect_admin_employee.php';

header('Content-Type: application/json; charset=utf-8');

function input(): array {
  $ct = $_SERVER['CONTENT_TYPE'] ?? '';
  if (stripos($ct,'application/json')!==false) {
    $raw=file_get_contents('php://input'); $d=json_decode($raw,true);
    if (json_last_error()===JSON_ERROR_NONE) return $d?:[];
  }
  return $_POST ?: [];
}

if (!in_array($_SERVER['REQUEST_METHOD'] ?? 'GET', ['POST','DELETE'], true)) {
  http_response_code(405); echo json_encode(['error'=>'Method not allowed']); exit;
}

$d = input();
$id = isset($_GET['id']) ? (int)$_GET['id'] : (int)($d['id'] ?? 0);
if ($id <= 0) { http_response_code(400); echo json_encode(['error'=>'id is required']); exit; }

$pdo = db();

// get product_id before delete
$cur=$pdo->prepare("SELECT product_id FROM product_specifications WHERE id=:id");
$cur->execute([':id'=>$id]);
$spec = $cur->fetch();
if (!$spec) { http_response_code(404); echo json_encode(['error'=>'Spec not found']); exit; }

$pid = (int)$spec['product_id'];

$del = $pdo->prepare("DELETE FROM product_specifications WHERE id = :id");
$del->execute([':id'=>$id]);

// return remaining list for that product (optional)
$s = $pdo->prepare("SELECT * FROM product_specifications WHERE product_id = :pid ORDER BY spec_group IS NULL, spec_group, display_order, spec_name");
$s->execute([':pid'=>$pid]);
$rows = $s->fetchAll();

echo json_encode(['success'=>true,'message'=>'Spec deleted','data'=>$rows]);