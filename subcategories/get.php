<?php
require_once __DIR__ . '/../config/cors.php';
require_once __DIR__ . '/../config/db.php';

if ($_SERVER['REQUEST_METHOD'] !== 'GET') {
  http_response_code(405); echo json_encode(['error'=>'Method not allowed']); exit;
}
$pdo = db();

if (isset($_GET['id'])) {
  $id = (int)$_GET['id'];
  $s = $pdo->prepare("SELECT * FROM subcategories WHERE id = :id");
  $s->execute([':id'=>$id]);
  $row = $s->fetch();
  if (!$row) { http_response_code(404); echo json_encode(['error'=>'Not found']); exit; }
  echo json_encode(['success'=>true,'data'=>$row]); exit;
}

if (!empty($_GET['slug'])) {
  $slug = trim($_GET['slug']);
  $s = $pdo->prepare("SELECT * FROM subcategories WHERE slug = :slug");
  $s->execute([':slug'=>$slug]);
  $row = $s->fetch();
  if (!$row) { http_response_code(404); echo json_encode(['error'=>'Not found']); exit; }
  echo json_encode(['success'=>true,'data'=>$row]); exit;
}

$page = max(1, (int)($_GET['page'] ?? 1));
$limit = min(100, max(1, (int)($_GET['limit'] ?? 20)));
$offset = ($page - 1) * $limit;

$where = []; $p = [];

if (isset($_GET['category_id'])) { $where[]='category_id = :cid'; $p[':cid'] = (int)$_GET['category_id']; }
if (isset($_GET['type']) && in_array($_GET['type'], ['videography','photography','both'], true)) {
  $where[] = 'type = :type'; $p[':type'] = $_GET['type'];
}
if (isset($_GET['is_active'])) { $where[]='is_active = :ia'; $p[':ia']=(int)!!$_GET['is_active']; }
if (!empty($_GET['q'])) { $p[':q'] = '%'.trim($_GET['q']).'%'; $where[]='(name LIKE :q OR slug LIKE :q)'; }

$w = $where ? 'WHERE '.implode(' AND ', $where) : '';

$c = $pdo->prepare("SELECT COUNT(*) FROM subcategories $w"); $c->execute($p);
$total = (int)$c->fetchColumn();

$sql = "SELECT * FROM subcategories $w ORDER BY created_at DESC LIMIT :l OFFSET :o";
$s = $pdo->prepare($sql);
foreach ($p as $k=>$v) $s->bindValue($k, $v);
$s->bindValue(':l', $limit, PDO::PARAM_INT);
$s->bindValue(':o', $offset, PDO::PARAM_INT);
$s->execute();
$rows = $s->fetchAll();

echo json_encode(['success'=>true,'data'=>$rows,'pagination'=>[
  'page'=>$page,'limit'=>$limit,'total'=>$total,'pages'=>(int)ceil($total/$limit)
]]);