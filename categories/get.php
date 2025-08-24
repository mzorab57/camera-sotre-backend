<?php
require_once __DIR__ . '/../config/cors.php';
require_once __DIR__ . '/../config/db.php';

if ($_SERVER['REQUEST_METHOD'] !== 'GET') {
  http_response_code(405);
  echo json_encode(['error' => 'Method not allowed']); exit;
}
$pdo = db();

if (isset($_GET['id'])) {
  $id = (int)$_GET['id'];
  $stmt = $pdo->prepare("SELECT * FROM categories WHERE id=:id");
  $stmt->execute([':id'=>$id]);
  $row = $stmt->fetch();
  if (!$row) { http_response_code(404); echo json_encode(['error'=>'Not found']); exit; }
  echo json_encode(['success'=>true,'data'=>$row]); exit;
}

if (!empty($_GET['slug'])) {
  $slug = trim($_GET['slug']);
  $stmt = $pdo->prepare("SELECT * FROM categories WHERE slug=:slug");
  $stmt->execute([':slug'=>$slug]);
  $row = $stmt->fetch();
  if (!$row) { http_response_code(404); echo json_encode(['error'=>'Not found']); exit; }
  echo json_encode(['success'=>true,'data'=>$row]); exit;
}

$page = max(1, (int)($_GET['page'] ?? 1));
$limit = min(100, max(1, (int)($_GET['limit'] ?? 20)));
$offset = ($page-1)*$limit;

$where=[]; $params=[];
if (!empty($_GET['q'])) { $where[]="(name LIKE :q OR slug LIKE :q)"; $params[':q']='%'.trim($_GET['q']).'%'; }
if (isset($_GET['is_active'])) { $where[]="is_active=:ia"; $params[':ia']=(int)!!$_GET['is_active']; }

$w = $where ? 'WHERE '.implode(' AND ',$where) : '';
$c = $pdo->prepare("SELECT COUNT(*) FROM categories $w");
$c->execute($params);
$total = (int)$c->fetchColumn();

$sql = "SELECT * FROM categories $w ORDER BY created_at DESC LIMIT :l OFFSET :o";
$s = $pdo->prepare($sql);
foreach ($params as $k=>$v) $s->bindValue($k,$v);
$s->bindValue(':l',$limit,PDO::PARAM_INT);
$s->bindValue(':o',$offset,PDO::PARAM_INT);
$s->execute();
$rows = $s->fetchAll();

echo json_encode(['success'=>true,'data'=>$rows,'pagination'=>[
  'page'=>$page,'limit'=>$limit,'total'=>$total,'pages'=>(int)ceil($total/$limit)
]]);