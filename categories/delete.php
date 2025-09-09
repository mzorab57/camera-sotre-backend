<?php
// categories/delete.php
require_once __DIR__ . '/../config/cors.php';
require_once __DIR__ . '/../config/db.php';
require_once __DIR__ . '/../middleware/protect_admin_employee.php';


function input() {
  $ct = $_SERVER['CONTENT_TYPE'] ?? '';
  if (stripos($ct,'application/json')!==false) {
    $raw=file_get_contents('php://input'); $d=json_decode($raw,true);
    if (json_last_error()===JSON_ERROR_NONE) return $d?:[];
  }
  return $_POST?:[];
}

if (!in_array($_SERVER['REQUEST_METHOD'], ['POST','DELETE'], true)) {
  http_response_code(405); echo json_encode(['error'=>'Method not allowed']); exit;
}
$in=input();
$id = isset($_GET['id']) ? (int)$_GET['id'] : (int)($in['id'] ?? 0);
if ($id<=0) { http_response_code(400); echo json_encode(['error'=>'id is required']); exit; }

$restore = isset($_GET['restore']) ? (int)$_GET['restore'] : (int)($in['restore'] ?? 0);
$hard    = isset($_GET['hard']) ? (int)$_GET['hard'] : (int)($in['hard'] ?? 0);

try {
  $pdo=db();
  if ($restore) {
    $u=$pdo->prepare("UPDATE categories SET is_active=1 WHERE id=:id"); $u->execute([':id'=>$id]);
    $msg='Category restored (is_active=1)';
  } elseif ($hard) {
    // تەنها admin دەتوانێت hard delete بکات
    require_once __DIR__ . '/../middleware/protect_admin.php';

    // ئاگاداری: ON DELETE CASCADE بۆ subcategories
    $d=$pdo->prepare("DELETE FROM categories WHERE id=:id"); $d->execute([':id'=>$id]);
    $msg='Category hard-deleted';
  } else {
    $u=$pdo->prepare("UPDATE categories SET is_active=0 WHERE id=:id"); $u->execute([':id'=>$id]);
    $msg='Category deactivated (is_active=0)';
  }

  $get=$pdo->prepare("SELECT * FROM categories WHERE id=:id"); $get->execute([':id'=>$id]);
  $row=$get->fetch(); // لە hard delete دا دەتوانێت null بێت

  echo json_encode(['success'=>true,'message'=>$msg,'data'=>$row]);
} catch(PDOException $e){
  http_response_code(500); echo json_encode(['error'=>'Database error']);
}