<?php
require_once __DIR__ . '/../config/cors.php';
require_once __DIR__ . '/../config/db.php';
require_once __DIR__ . '/../middleware/protect_admin_employee.php';

header('Content-Type: application/json; charset=utf-8');

function input(): array { $ct=$_SERVER['CONTENT_TYPE']??''; if (stripos($ct,'application/json')!==false){$raw=file_get_contents('php://input');$d=json_decode($raw,true); if(json_last_error()===JSON_ERROR_NONE)return $d?:[];} return $_POST?:[]; }

$method=$_SERVER['REQUEST_METHOD']??'GET';
if (!in_array($method,['POST','DELETE'],true)) { http_response_code(405); echo json_encode(['error'=>'Method not allowed']); exit; }

$d=input();
$product_id=(int)($d['product_id'] ?? ($_GET['product_id'] ?? 0));
$tag_ids = isset($d['tag_ids']) ? (array)$d['tag_ids'] : (isset($_GET['tag_ids']) ? (array)$_GET['tag_ids'] : null);
$slugs   = isset($d['slugs']) ? (array)$d['slugs'] : (isset($_GET['slugs']) ? (array)$_GET['slugs'] : null);
$all     = !empty($d['all']) || (!empty($_GET['all']));

if ($product_id<=0){ http_response_code(400); echo json_encode(['error'=>'product_id is required']); exit; }

$pdo=db();

// verify product
$chk=$pdo->prepare("SELECT id FROM products WHERE id=:id"); $chk->execute([':id'=>$product_id]); if(!$chk->fetch()){ http_response_code(422); echo json_encode(['error'=>'product_id not found']); exit; }

try {
  if ($all) {
    $pdo->prepare("DELETE FROM product_tags WHERE product_id=:pid")->execute([':pid'=>$product_id]);
  } else {
    $ids=[];

    if ($slugs) {
      // resolve slugs to ids
      $slugs = array_values(array_unique(array_map('strval', $slugs)));
      $in = implode(',', array_fill(0, count($slugs), '?'));
      $sel=$pdo->prepare("SELECT id FROM tags WHERE slug IN ($in)");
      $sel->execute($slugs);
      $ids = array_map('intval', $sel->fetchAll(PDO::FETCH_COLUMN));
    }

    if ($tag_ids) {
      foreach ($tag_ids as $tid){ $tid=(int)$tid; if($tid>0) $ids[]=$tid; }
    }

    $ids = array_values(array_unique($ids));
    if (!$ids){ http_response_code(400); echo json_encode(['error'=>'Provide tag_ids or slugs']); exit; }

    $del=$pdo->prepare("DELETE FROM product_tags WHERE product_id=:pid AND tag_id=:tid");
    foreach ($ids as $tid) { $del->execute([':pid'=>$product_id, ':tid'=>$tid]); }
  }

  // return remaining tags
  $q=$pdo->prepare("SELECT t.* FROM tags t JOIN product_tags pt ON pt.tag_id=t.id WHERE pt.product_id=:pid ORDER BY t.name");
  $q->execute([':pid'=>$product_id]);
  echo json_encode(['success'=>true,'data'=>$q->fetchAll()]);

} catch (PDOException $e) {
  http_response_code(500); echo json_encode(['error'=>'Database error','code'=>$e->getCode()]);
}