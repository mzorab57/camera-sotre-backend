<?php
require_once __DIR__ . '/../config/cors.php';
require_once __DIR__ . '/../config/db.php';

if ($_SERVER['REQUEST_METHOD']!=='GET') { http_response_code(405); echo json_encode(['error'=>'Method not allowed']); exit; }
$pdo = db();

if (isset($_GET['id'])) {
  $id=(int)$_GET['id'];
  $s=$pdo->prepare("SELECT p.*,
    (SELECT image_url FROM product_images WHERE product_id=p.id AND is_primary=1 LIMIT 1) AS primary_image_url,
    s.name AS subcategory_name,
    c.name AS category_name
    FROM products p
    LEFT JOIN subcategories s ON p.subcategory_id = s.id
    LEFT JOIN categories c ON s.category_id = c.id
    WHERE p.id=:id");
  $s->execute([':id'=>$id]);
  $row=$s->fetch();
  if(!$row){ http_response_code(404); echo json_encode(['error'=>'Not found']); exit; }
  echo json_encode(['success'=>true,'data'=>$row]); exit;
}

if (!empty($_GET['slug'])) {
  $slug=trim($_GET['slug']);
  $s=$pdo->prepare("SELECT p.*,
    (SELECT image_url FROM product_images WHERE product_id=p.id AND is_primary=1 LIMIT 1) AS primary_image_url,
    s.name AS subcategory_name,
    c.name AS category_name
    FROM products p
    LEFT JOIN subcategories s ON p.subcategory_id = s.id
    LEFT JOIN categories c ON s.category_id = c.id
    WHERE p.slug=:slug");
  $s->execute([':slug'=>$slug]);
  $row=$s->fetch();
  if(!$row){ http_response_code(404); echo json_encode(['error'=>'Not found']); exit; }
  echo json_encode(['success'=>true,'data'=>$row]); exit;
}

$page=max(1,(int)($_GET['page']??1));
$limit=min(100,max(1,(int)($_GET['limit']??20)));
$offset=($page-1)*$limit;

$where=[]; $p=[];

if (isset($_GET['subcategory_id'])) { $where[]='p.subcategory_id=:sid'; $p[':sid']=(int)$_GET['subcategory_id']; }
if (isset($_GET['category_id'])) { $where[]='p.category_id=:cid'; $p[':cid']=(int)$_GET['category_id']; }
if (isset($_GET['type']) && in_array($_GET['type'],['videography','photography','both'],true)) { $where[]='p.type=:type'; $p[':type']=$_GET['type']; }
if (!empty($_GET['brand'])) { $where[]='p.brand=:brand'; $p[':brand']=trim($_GET['brand']); }
if (isset($_GET['is_active'])) { $where[]='p.is_active=:ia'; $p[':ia']=(int)!!$_GET['is_active']; }
if (isset($_GET['is_featured'])) { $where[]='p.is_featured=:if'; $p[':if']=(int)!!$_GET['is_featured']; }
if (isset($_GET['min_price'])) { $where[]='p.price>=:minp'; $p[':minp']= (float)$_GET['min_price']; }
if (isset($_GET['max_price'])) { $where[]='p.price<=:maxp'; $p[':maxp']= (float)$_GET['max_price']; }
if (!empty($_GET['q'])) {
  $q='%'.trim($_GET['q']).'%';
  $where[]='(p.name LIKE :q OR p.model LIKE :q OR p.sku LIKE :q OR p.description LIKE :q)';
  $p[':q']=$q;
}

$w=$where?'WHERE '.implode(' AND ',$where):'';

$c=$pdo->prepare("SELECT COUNT(*) FROM products p LEFT JOIN subcategories s ON p.subcategory_id = s.id LEFT JOIN categories c ON s.category_id = c.id $w"); $c->execute($p); $total=(int)$c->fetchColumn();

$sql="SELECT p.*,
 (SELECT image_url FROM product_images WHERE product_id=p.id AND is_primary=1 LIMIT 1) AS primary_image_url,
 s.name AS subcategory_name,
 c.name AS category_name
 FROM products p
 LEFT JOIN subcategories s ON p.subcategory_id = s.id
 LEFT JOIN categories c ON s.category_id = c.id
 $w
 ORDER BY p.created_at DESC
 LIMIT :l OFFSET :o";
$s=$pdo->prepare($sql);
foreach($p as $k=>$v){ $s->bindValue($k,$v); }
$s->bindValue(':l',$limit,PDO::PARAM_INT);
$s->bindValue(':o',$offset,PDO::PARAM_INT);
$s->execute();
$rows=$s->fetchAll();

// Add this after line 60 (after the main query)
require_once __DIR__ . '/../utils/discount_calculator.php';

echo json_encode(['success'=>true,'data'=>$rows,'pagination'=>[
  'page'=>$page,'limit'=>$limit,'total'=>$total,'pages'=>(int)ceil($total/$limit)
]]);

// After fetching products, calculate discounts
foreach ($rows as &$product) {
    $discount = calculateProductDiscount(
        $product['id'], 
        $product['subcategory_id'], 
        $product['category_id'] ?? null
    );
    
    if ($discount) {
        $product['active_discount'] = $discount;
        $product['discounted_price'] = applyDiscount($product['price'], $discount);
        $product['discount_amount'] = $product['price'] - $product['discounted_price'];
        $product['discount_percentage'] = round(($product['discount_amount'] / $product['price']) * 100, 2);
    } else {
        $product['active_discount'] = null;
        $product['discounted_price'] = $product['discount_price'] ?: $product['price'];
        $product['discount_amount'] = 0;
        $product['discount_percentage'] = 0;
    }
}