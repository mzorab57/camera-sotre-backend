<?php
require_once __DIR__ . '/../config/cors.php';
require_once __DIR__ . '/../config/db.php';
require_once __DIR__ . '/../middleware/protect_admin_employee.php';

function input(): array {
  $ct=$_SERVER['CONTENT_TYPE']??''; if (stripos($ct,'application/json')!==false) {
    $raw=file_get_contents('php://input'); $d=json_decode($raw,true); if(json_last_error()===JSON_ERROR_NONE) return $d?:[];
  } return $_POST?:[];
}

$method = $_SERVER['REQUEST_METHOD'] ?? 'GET';
if (!in_array($method,['POST','DELETE'],true)) { http_response_code(405); echo json_encode(['error'=>'Method not allowed']); exit; }

$d=input();
$id = isset($_GET['id']) ? (int)$_GET['id'] : (int)($d['id'] ?? 0);
if ($id<=0) { http_response_code(400); echo json_encode(['error'=>'id is required']); exit; }

$restore = isset($_GET['restore']) ? (int)$_GET['restore'] : (int)($d['restore'] ?? 0);
$hard    = isset($_GET['hard']) ? (int)$_GET['hard'] : (int)($d['hard'] ?? 0);

$pdo=db();

try {
  if ($restore) {
    $pdo->prepare("UPDATE products SET is_active=1 WHERE id=:id")->execute([':id'=>$id]);
    $msg='Product restored (is_active=1)';
  } elseif ($hard) {
    // تەنها admin دەتوانێت hard delete بکات
    require_once __DIR__ . '/../middleware/protect_admin.php';
    $pdo->prepare("DELETE FROM products WHERE id=:id")->execute([':id'=>$id]);
    $msg='Product hard-deleted';
  } else {
    $pdo->prepare("UPDATE products SET is_active=0 WHERE id=:id")->execute([':id'=>$id]);
    $msg='Product deactivated (is_active=0)';
  }

  $g=$pdo->prepare("SELECT p.*,
   (SELECT image_url FROM product_images WHERE product_id=p.id AND is_primary=1 LIMIT 1) AS primary_image_url
   FROM products p WHERE p.id=:id");
  $g->execute([':id'=>$id]); $row=$g->fetch();

  echo json_encode(['success'=>true,'message'=>$msg,'data'=>$row]);

} catch (PDOException $e) {
  http_response_code(500); echo json_encode(['error'=>'Database error','code'=>$e->getCode()]);
}