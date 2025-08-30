<?php
require_once __DIR__ . '/../config/cors.php';
require_once __DIR__ . '/../config/db.php';
header('Content-Type: application/json; charset=utf-8');

function makeFullUrl(string $rel): ?string {
  $host=$_SERVER['HTTP_HOST']??''; if(!$host)return null;
  $scheme=(!empty($_SERVER['HTTPS'])&&$_SERVER['HTTPS']!=='off')?'https':'http';
  $twoUp=dirname(dirname($_SERVER['SCRIPT_NAME']??''))?:'';
  $base=rtrim(str_replace('\\','/',$twoUp),'/');
  $rel='/'.ltrim($rel,'/');
  return $scheme.'://'.$host.$base.$rel;
}

if ($_SERVER['REQUEST_METHOD']!=='GET') { http_response_code(405); echo json_encode(['error'=>'Method not allowed']); exit; }
$pdo = db();

if (isset($_GET['id'])) {
  $id=(int)$_GET['id'];
  $s=$pdo->prepare("SELECT * FROM product_images WHERE id=:id");
  $s->execute([':id'=>$id]);
  $row=$s->fetch();
  if(!$row){ http_response_code(404); echo json_encode(['error'=>'Not found']); exit; }
  if (!empty($row['image_url']) && strpos($row['image_url'],'http')!==0) {
    $row['image_full_url']=makeFullUrl($row['image_url']);
  }
  echo json_encode(['success'=>true,'data'=>$row]); exit;
}

if (!isset($_GET['product_id'])) { http_response_code(400); echo json_encode(['error'=>'product_id is required']); exit; }
$pid=(int)$_GET['product_id'];

$s=$pdo->prepare("SELECT * FROM product_images WHERE product_id=:pid ORDER BY is_primary DESC, display_order ASC, id ASC");
$s->execute([':pid'=>$pid]);
$rows=$s->fetchAll();

foreach($rows as &$r){
  if (!empty($r['image_url']) && strpos($r['image_url'],'http')!==0) {
    $r['image_full_url']=makeFullUrl($r['image_url']);
  }
}
echo json_encode(['success'=>true,'data'=>$rows]);