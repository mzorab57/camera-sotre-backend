<?php
require_once __DIR__ . '/../config/cors.php';
require_once __DIR__ . '/../config/db.php';
require_once __DIR__ . '/../middleware/protect_admin_employee.php';

header('Content-Type: application/json; charset=utf-8');

function deleteLocalIfAny(?string $url): void {
  if (!$url) return;
  if (strpos($url, '/uploads/products/') === 0) {
    $path = dirname(__DIR__) . $url;
    if (is_file($path)) @unlink($path);
  }
}

$method=$_SERVER['REQUEST_METHOD']??'GET';
if (!in_array($method,['POST','DELETE'],true)) { http_response_code(405); echo json_encode(['error'=>'Method not allowed']); exit; }

$in = $_POST ?: [];
$id = isset($_GET['id']) ? (int)$_GET['id'] : (int)($in['id'] ?? 0);
if ($id<=0) { http_response_code(400); echo json_encode(['error'=>'id is required']); exit; }

$pdo=db();

$cur=$pdo->prepare("SELECT * FROM product_images WHERE id=:id"); $cur->execute([':id'=>$id]); $img=$cur->fetch();
if(!$img){ http_response_code(404); echo json_encode(['error'=>'Image not found']); exit; }

$pid = (int)$img['product_id'];
$wasPrimary = (int)$img['is_primary'] === 1;

$del=$pdo->prepare("DELETE FROM product_images WHERE id=:id"); $del->execute([':id'=>$id]);

// delete local file if needed
deleteLocalIfAny($img['image_url']);

// if deleted primary, promote next image
if ($wasPrimary) {
  $chk=$pdo->prepare("SELECT id FROM product_images WHERE product_id=:pid AND is_primary=1 LIMIT 1");
  $chk->execute([':pid'=>$pid]);
  if(!$chk->fetch()){
    $pick=$pdo->prepare("SELECT id FROM product_images WHERE product_id=:pid ORDER BY display_order, id LIMIT 1");
    $pick->execute([':pid'=>$pid]);
    $row=$pick->fetch();
    if($row){
      $pdo->prepare("UPDATE product_images SET is_primary = (id = :id) WHERE product_id = :pid")
          ->execute([':id'=>$row['id'], ':pid'=>$pid]);
    }
  }
}

// return remaining list
$s=$pdo->prepare("SELECT * FROM product_images WHERE product_id=:pid ORDER BY is_primary DESC, display_order ASC, id ASC");
$s->execute([':pid'=>$pid]);
$rows=$s->fetchAll();
echo json_encode(['success'=>true,'data'=>$rows]);