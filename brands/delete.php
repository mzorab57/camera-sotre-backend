<?php
require_once __DIR__ . '/../config/cors.php';
require_once __DIR__ . '/../config/db.php';
header('Content-Type: application/json; charset=utf-8');

function input(): array {
  $ct=$_SERVER['CONTENT_TYPE']??''; if (stripos($ct,'application/json')!==false) {
    $raw=file_get_contents('php://input'); $d=json_decode($raw,true); if(json_last_error()===JSON_ERROR_NONE) return $d?:[];
  } return $_POST?:[];
}
function deleteLocalIfAny(?string $url): void {
  if (!$url) return;
  if (strpos($url, '/uploads/brands/') === 0) {
    $path = dirname(__DIR__) . $url;
    if (is_file($path)) @unlink($path);
  }
}

$method=$_SERVER['REQUEST_METHOD']??'GET';
if (!in_array($method,['POST','DELETE'],true)) { http_response_code(405); echo json_encode(['error'=>'Method not allowed']); exit; }

$d = input();
$id = isset($_GET['id']) ? (int)$_GET['id'] : (int)($d['id'] ?? 0);
if ($id<=0) { http_response_code(400); echo json_encode(['error'=>'id is required']); exit; }

$restore = isset($_GET['restore']) ? (int)$_GET['restore'] : (int)($d['restore'] ?? 0);
$hard    = isset($_GET['hard']) ? (int)$_GET['hard'] : (int)($d['hard'] ?? 0);

$pdo = db();

// Load current logo before delete
$cur=$pdo->prepare("SELECT * FROM brands WHERE id=:id"); $cur->execute([':id'=>$id]); $brand=$cur->fetch();
if(!$brand){ http_response_code(404); echo json_encode(['error'=>'Brand not found']); exit; }

try {
  if ($restore) {
    $pdo->prepare("UPDATE brands SET is_active=1 WHERE id=:id")->execute([':id'=>$id]);
    $msg='Brand restored (is_active=1)';
  } elseif ($hard) {
    $pdo->prepare("DELETE FROM brands WHERE id=:id")->execute([':id'=>$id]);
    // delete local logo if any
    deleteLocalIfAny($brand['logo_url']);
    $msg='Brand hard-deleted';
  } else {
    $pdo->prepare("UPDATE brands SET is_active=0 WHERE id=:id")->execute([':id'=>$id]);
    $msg='Brand deactivated (is_active=0)';
  }

  // return brand (might be null after hard delete)
  $g=$pdo->prepare("SELECT * FROM brands WHERE id=:id"); $g->execute([':id'=>$id]); $row=$g->fetch();
  echo json_encode(['success'=>true,'message'=>$msg,'data'=>$row]);

} catch (PDOException $e) {
  http_response_code(500); echo json_encode(['error'=>'Database error','code'=>$e->getCode()]);
}