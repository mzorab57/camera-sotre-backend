<?php
require_once __DIR__ . '/../config/cors.php';
require_once __DIR__ . '/../config/db.php';
header('Content-Type: application/json; charset=utf-8');

if ($_SERVER['REQUEST_METHOD'] !== 'GET') { http_response_code(405); echo json_encode(['error'=>'Method not allowed']); exit; }
$pdo = db();

if (isset($_GET['id'])) {
  $id=(int)$_GET['id'];
  $s=$pdo->prepare("SELECT * FROM tags WHERE id=:id"); $s->execute([':id'=>$id]);
  $row=$s->fetch(); if(!$row){ http_response_code(404); echo json_encode(['error'=>'Not found']); exit; }
  echo json_encode(['success'=>true,'data'=>$row]); exit;
}

if (!empty($_GET['slug'])) {
  $slug=trim($_GET['slug']);
  $s=$pdo->prepare("SELECT * FROM tags WHERE slug=:slug"); $s->execute([':slug'=>$slug]);
  $row=$s->fetch(); if(!$row){ http_response_code(404); echo json_encode(['error'=>'Not found']); exit; }
  echo json_encode(['success'=>true,'data'=>$row]); exit;
}

# Optional: tags of a product
if (isset($_GET['product_id'])) {
  $pid=(int)$_GET['product_id'];
  $q="SELECT t.* FROM tags t JOIN product_tags pt ON pt.tag_id=t.id WHERE pt.product_id=:pid ORDER BY t.name";
  $s=$pdo->prepare($q); $s->execute([':pid'=>$pid]); echo json_encode(['success'=>true,'data'=>$s->fetchAll()]); exit;
}

$page=max(1,(int)($_GET['page']??1)); $limit=min(100,max(1,(int)($_GET['limit']??20))); $offset=($page-1)*$limit;
$where=[]; $p=[];
if (!empty($_GET['q'])){ $p[':q']='%'.trim($_GET['q']).'%'; $where[]='(name LIKE :q OR slug LIKE :q)'; }
$w=$where?'WHERE '.implode(' AND ',$where):'';

$c=$pdo->prepare("SELECT COUNT(*) FROM tags $w"); $c->execute($p); $total=(int)$c->fetchColumn();
$sql="SELECT * FROM tags $w ORDER BY name LIMIT :l OFFSET :o";
$s=$pdo->prepare($sql); foreach($p as $k=>$v) $s->bindValue($k,$v); $s->bindValue(':l',$limit,PDO::PARAM_INT); $s->bindValue(':o',$offset,PDO::PARAM_INT); $s->execute();
echo json_encode(['success'=>true,'data'=>$s->fetchAll(),'pagination'=>['page'=>$page,'limit'=>$limit,'total'=>$total,'pages'=>(int)ceil($total/$limit)]]);