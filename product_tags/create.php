<?php
require_once __DIR__ . '/../config/cors.php';
require_once __DIR__ . '/../config/db.php';
require_once __DIR__ . '/../middleware/protect_admin_employee.php';

header('Content-Type: application/json; charset=utf-8');

function input(): array { $ct=$_SERVER['CONTENT_TYPE']??''; if (stripos($ct,'application/json')!==false){$raw=file_get_contents('php://input');$d=json_decode($raw,true); if(json_last_error()===JSON_ERROR_NONE) return $d?:[];} return $_POST?:[]; }
if (!function_exists('slugify')) { function slugify($t){$t=trim($t);$t=preg_replace('~[^\pL\d]+~u','-',$t);$t=trim($t,'-');$t=strtolower($t);$t=preg_replace('~[^-\w]+~','',$t);return $t?:'tag';} }

if ($_SERVER['REQUEST_METHOD'] !== 'POST') { http_response_code(405); echo json_encode(['error'=>'Method not allowed']); exit; }

$d = input();
$product_id = (int)($d['product_id'] ?? 0);
$tag_ids = isset($d['tag_ids']) ? (array)$d['tag_ids'] : null;
$slugs = isset($d['slugs']) ? (array)$d['slugs'] : null;
$auto_create = !empty($d['auto_create']);

if ($product_id <= 0) { http_response_code(400); echo json_encode(['error'=>'product_id is required']); exit; }
if (!$tag_ids && !$slugs) { http_response_code(400); echo json_encode(['error'=>'Provide tag_ids or slugs']); exit; }

$pdo = db();

// verify product
$chk=$pdo->prepare("SELECT id FROM products WHERE id=:id"); $chk->execute([':id'=>$product_id]); if(!$chk->fetch()){ http_response_code(422); echo json_encode(['error'=>'product_id not found']); exit; }

$ids = [];

try {
  if ($slugs) {
    // normalize slugs
    $slugs = array_values(array_unique(array_map(function($s){ return slugify((string)$s); }, $slugs)));
    if (!$slugs) { http_response_code(422); echo json_encode(['error'=>'Empty slugs']); exit; }

    // fetch existing tags by slugs
    $in = implode(',', array_fill(0, count($slugs), '?'));
    $sel = $pdo->prepare("SELECT id, slug FROM tags WHERE slug IN ($in)");
    $sel->execute($slugs);
    $found = $sel->fetchAll(PDO::FETCH_KEY_PAIR); // slug => id

    // create missing if auto_create
    foreach ($slugs as $s) {
      if (!isset($found[$s])) {
        if (!$auto_create) continue;
        $name = ucwords(str_replace('-', ' ', $s));
        try {
          $ins = $pdo->prepare("INSERT INTO tags (name, slug) VALUES (:name,:slug)");
          $ins->execute([':name'=>$name, ':slug'=>$s]);
          $found[$s] = (int)$pdo->lastInsertId();
        } catch (PDOException $e) {
          // ignore duplicates race
          if ($e->getCode()==='23000') {
            $r=$pdo->prepare("SELECT id FROM tags WHERE slug=:slug"); $r->execute([':slug'=>$s]);
            $found[$s]=(int)$r->fetchColumn();
          } else { throw $e; }
        }
      }
    }
    $ids = array_values(array_filter(array_map('intval', $found)));
  }

  if ($tag_ids) {
    foreach ($tag_ids as $tid) {
      $tid = (int)$tid;
      if ($tid > 0) $ids[] = $tid;
    }
  }

  $ids = array_values(array_unique($ids));
  if (!$ids) { http_response_code(422); echo json_encode(['error'=>'No valid tags to attach']); exit; }

  // attach using INSERT IGNORE to skip duplicates
  $stmt = $pdo->prepare("INSERT IGNORE INTO product_tags (product_id, tag_id) VALUES (:pid, :tid)");
  foreach ($ids as $tid) {
    // verify tag exists
    $tchk=$pdo->prepare("SELECT id FROM tags WHERE id=:id"); $tchk->execute([':id'=>$tid]); if(!$tchk->fetch()) continue;
    $stmt->execute([':pid'=>$product_id, ':tid'=>$tid]);
  }

  // return tags of this product
  $q = $pdo->prepare("SELECT t.* FROM tags t JOIN product_tags pt ON pt.tag_id=t.id WHERE pt.product_id=:pid ORDER BY t.name");
  $q->execute([':pid'=>$product_id]);
  echo json_encode(['success'=>true,'data'=>$q->fetchAll()]);

} catch (PDOException $e) {
  http_response_code(500); echo json_encode(['error'=>'Database error','code'=>$e->getCode()]);
}