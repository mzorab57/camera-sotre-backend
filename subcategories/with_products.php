<?php
require_once __DIR__ . '/../config/cors.php';
require_once __DIR__ . '/../config/db.php';
header('Content-Type: application/json; charset=utf-8');

$pdo = db();

$category_id   = isset($_GET['category_id']) ? (int)$_GET['category_id'] : 0;
$category_slug = isset($_GET['category_slug']) ? trim($_GET['category_slug']) : '';
$limitPer      = isset($_GET['per_subcat_limit']) ? max(1, min(24, (int)$_GET['per_subcat_limit'])) : 6;

if ($category_id <= 0 && $category_slug === '') {
  http_response_code(400); echo json_encode(['error'=>'category_id or category_slug is required']); exit;
}

if ($category_id <= 0 && $category_slug !== '') {
  $r=$pdo->prepare("SELECT id,name,slug FROM categories WHERE slug=:slug AND is_active=1");
  $r->execute([':slug'=>$category_slug]); $cat=$r->fetch();
  if(!$cat){ http_response_code(404); echo json_encode(['error'=>'Category not found']); exit; }
  $category_id=(int)$cat['id'];
} else {
  $r=$pdo->prepare("SELECT id,name,slug FROM categories WHERE id=:id AND is_active=1");
  $r->execute([':id'=>$category_id]); $cat=$r->fetch();
  if(!$cat){ http_response_code(404); echo json_encode(['error'=>'Category not found']); exit; }
}

$sqlSub = "
  SELECT s.id, s.name, s.slug, s.type, s.image_url, s.is_active,
         COUNT(p.id) AS product_count
  FROM subcategories s
  LEFT JOIN products p ON p.subcategory_id = s.id AND p.is_active = 1
  WHERE s.category_id = :cid AND s.is_active = 1
  GROUP BY s.id
  ORDER BY s.name
";
$st=$pdo->prepare($sqlSub);
$st->execute([':cid'=>$category_id]);
$subs=$st->fetchAll(PDO::FETCH_ASSOC);

if (!$subs) { echo json_encode(['success'=>true,'category'=>$cat,'subcategories'=>[]]); exit; }

$productsBySub=[];
$q=$pdo->prepare("
  SELECT p.id, p.subcategory_id, p.name, p.slug, p.model, p.price, p.discount_price, p.brand, p.type, p.created_at,
         (SELECT image_url FROM product_images WHERE product_id=p.id AND is_primary=1 LIMIT 1) AS primary_image_url
  FROM products p
  WHERE p.is_active=1 AND p.subcategory_id=:sid
  ORDER BY p.created_at DESC, p.id DESC
  LIMIT :lim
");

foreach ($subs as $s) {
  $sid=(int)$s['id'];
  $q->bindValue(':sid',$sid,PDO::PARAM_INT);
  $q->bindValue(':lim',$limitPer,PDO::PARAM_INT);
  $q->execute();
  $productsBySub[$sid]=$q->fetchAll(PDO::FETCH_ASSOC);
}

$out=[];
foreach ($subs as $s) {
  $sid=(int)$s['id'];
  $s['products']=$productsBySub[$sid] ?? [];
  $out[]=$s;
}

echo json_encode([
  'success'=>true,
  'category'=>$cat,
  'subcategories'=>$out,
  'per_subcat_limit'=>$limitPer
]);