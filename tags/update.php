<?php
require_once __DIR__ . '/../config/cors.php';
require_once __DIR__ . '/../config/db.php';
require_once __DIR__ . '/../middleware/protect_admin_employee.php';

header('Content-Type: application/json; charset=utf-8');

function input(): array { $ct=$_SERVER['CONTENT_TYPE']??''; if (stripos($ct,'application/json')!==false){ $raw=file_get_contents('php://input'); $d=json_decode($raw,true); if(json_last_error()===JSON_ERROR_NONE) return $d?:[]; } return $_POST?:[]; }
if (!function_exists('slugify')) { function slugify($t){$t=trim($t);$t=preg_replace('~[^\pL\d]+~u','-',$t);$t=trim($t,'-');$t=strtolower($t);$t=preg_replace('~[^-\w]+~','',$t);return $t?:'tag';} }

$method=$_SERVER['REQUEST_METHOD']??'GET';
if (!in_array($method,['POST','PUT','PATCH'],true)) { http_response_code(405); echo json_encode(['error'=>'Method not allowed']); exit; }

$d=input();
$id = isset($_GET['id']) ? (int)$_GET['id'] : (int)($d['id'] ?? 0);
if ($id<=0){ http_response_code(400); echo json_encode(['error'=>'id is required']); exit; }

$pdo=db();
$cur=$pdo->prepare("SELECT * FROM tags WHERE id=:id"); $cur->execute([':id'=>$id]); $row=$cur->fetch();
if(!$row){ http_response_code(404); echo json_encode(['error'=>'Tag not found']); exit; }

$fields=[]; $p=[':id'=>$id];
if (array_key_exists('name',$d)) { $name=trim((string)$d['name']); if($name===''){http_response_code(422); echo json_encode(['error'=>'name cannot be empty']); exit;} $fields[]='name=:name'; $p[':name']=$name; }
if (array_key_exists('slug',$d)) { $slug=trim((string)$d['slug']); if($slug===''){ $slug=slugify($p[':name'] ?? $row['name']); } $fields[]='slug=:slug'; $p[':slug']=$slug; }
if (!$fields){ http_response_code(400); echo json_encode(['error'=>'No fields to update']); exit; }

try{
  $u=$pdo->prepare("UPDATE tags SET ".implode(', ',$fields)." WHERE id=:id"); $u->execute($p);
  $g=$pdo->prepare("SELECT * FROM tags WHERE id=:id"); $g->execute([':id'=>$id]);
  echo json_encode(['success'=>true,'data'=>$g->fetch()]);
}catch(PDOException $e){
  if($e->getCode()==='23000'){ http_response_code(409); echo json_encode(['error'=>'Duplicate name or slug']); exit; }
  http_response_code(500); echo json_encode(['error'=>'Database error']);
}