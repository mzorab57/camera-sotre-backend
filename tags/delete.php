<?php
require_once __DIR__ . '/../config/cors.php';
require_once __DIR__ . '/../config/db.php';
require_once __DIR__ . '/../middleware/protect_admin_employee.php';
header('Content-Type: application/json; charset=utf-8');

function input(): array { $ct=$_SERVER['CONTENT_TYPE']??''; if (stripos($ct,'application/json')!==false){$raw=file_get_contents('php://input'); $d=json_decode($raw,true); if(json_last_error()===JSON_ERROR_NONE) return $d?:[];} return $_POST?:[]; }

$method=$_SERVER['REQUEST_METHOD']??'GET';
if (!in_array($method,['POST','DELETE'],true)) { http_response_code(405); echo json_encode(['error'=>'Method not allowed']); exit; }

$d=input();
$id = isset($_GET['id']) ? (int)$_GET['id'] : (int)($d['id'] ?? 0);
if ($id<=0){ http_response_code(400); echo json_encode(['error'=>'id is required']); exit; }

$pdo=db();

$cur=$pdo->prepare("SELECT * FROM tags WHERE id=:id"); $cur->execute([':id'=>$id]); $tag=$cur->fetch();
if(!$tag){ http_response_code(404); echo json_encode(['error'=>'Tag not found']); exit; }

try{
  $pdo->prepare("DELETE FROM product_tags WHERE tag_id=:id")->execute([':id'=>$id]); // optional (CASCADE already handles)
  $pdo->prepare("DELETE FROM tags WHERE id=:id")->execute([':id'=>$id]);
  echo json_encode(['success'=>true,'message'=>'Tag deleted']);
}catch(PDOException $e){
  http_response_code(500); echo json_encode(['error'=>'Database error','code'=>$e->getCode()]);
}