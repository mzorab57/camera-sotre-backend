<?php
require_once __DIR__ . '/../config/cors.php';
require_once __DIR__ . '/../config/db.php';
header('Content-Type: application/json; charset=utf-8');

function input(): array {
  $ct=$_SERVER['CONTENT_TYPE']??''; if (stripos($ct,'application/json')!==false) {
    $raw=file_get_contents('php://input'); $d=json_decode($raw,true); if(json_last_error()===JSON_ERROR_NONE) return $d?:[];
  } return $_POST?:[];
}

$method=$_SERVER['REQUEST_METHOD']??'GET';
if (!in_array($method,['POST','PUT','PATCH'],true)) { http_response_code(405); echo json_encode(['error'=>'Method not allowed']); exit; }

$pdo=db();
$in=input();
$id = isset($_GET['id']) ? (int)$_GET['id'] : (int)($in['id'] ?? 0);
if ($id<=0) { http_response_code(400); echo json_encode(['error'=>'id is required']); exit; }

$cur=$pdo->prepare("SELECT * FROM product_images WHERE id=:id"); $cur->execute([':id'=>$id]); $img=$cur->fetch();
if(!$img){ http_response_code(404); echo json_encode(['error'=>'Image not found']); exit; }

$fields=[]; $p=[':id'=>$id];

// display_order
if (array_key_exists('display_order',$in)) {
  $fields[]='display_order = :ord';
  $p[':ord'] = (int)$in['display_order'];
}

// is_primary
$makePrimary = null;
if (array_key_exists('is_primary',$in)) {
  $makePrimary = (int)!!$in['is_primary'];
}

if ($fields) {
  $u=$pdo->prepare("UPDATE product_images SET ".implode(', ',$fields)." WHERE id=:id");
  $u->execute($p);
}

if ($makePrimary !== null) {
  if ($makePrimary === 1) {
    $pdo->prepare("UPDATE product_images SET is_primary = (id = :id) WHERE product_id = :pid")
        ->execute([':id'=>$id, ':pid'=>$img['product_id']]);
  } else {
    // disallow having zero primary: ignore makePrimary=0 if this was the current primary
    $pdo->prepare("UPDATE product_images SET is_primary = 0 WHERE id = :id")->execute([':id'=>$id]);
    // ensure at least one primary exists
    $chk=$pdo->prepare("SELECT id FROM product_images WHERE product_id=:pid AND is_primary=1 LIMIT 1");
    $chk->execute([':pid'=>$img['product_id']]);
    if(!$chk->fetch()){
      $pick=$pdo->prepare("SELECT id FROM product_images WHERE product_id=:pid ORDER BY display_order, id LIMIT 1");
      $pick->execute([':pid'=>$img['product_id']]);
      $row=$pick->fetch();
      if($row){
        $pdo->prepare("UPDATE product_images SET is_primary = (id = :id) WHERE product_id = :pid")
            ->execute([':id'=>$row['id'], ':pid'=>$img['product_id']]);
      }
    }
  }
}

$g=$pdo->prepare("SELECT * FROM product_images WHERE id=:id"); $g->execute([':id'=>$id]); $row=$g->fetch();
echo json_encode(['success'=>true,'data'=>$row]);