<?php
require_once __DIR__ . '/../config/cors.php';
require_once __DIR__ . '/../config/db.php';
header('Content-Type: application/json; charset=utf-8');

if ($_SERVER['REQUEST_METHOD']!=='GET'){ http_response_code(405); echo json_encode(['error'=>'Method not allowed']); exit; }
$pdo=db();

// Get all product-tag relationships
if (isset($_GET['get_all'])) {
  try {
    $page=max(1,(int)($_GET['page']??1)); $limit=min(100,max(1,(int)($_GET['limit']??20))); $offset=($page-1)*$limit;
    
    // First check if product_tags table exists and has data
    $count=$pdo->prepare("SELECT COUNT(*) FROM product_tags");
    $count->execute(); $total=(int)$count->fetchColumn();
    
    if ($total === 0) {
      echo json_encode(['success'=>true,'data'=>[],'pagination'=>[
        'page'=>$page,'limit'=>$limit,'total'=>0,'pages'=>0
      ]]); exit;
    }
    
    // Query with JOINs to get product and tag details
    $sql="SELECT pt.product_id, pt.tag_id, 
                   p.name as product_name, p.price as product_price,
                   t.name as tag_name, t.slug as tag_slug
            FROM product_tags pt 
            LEFT JOIN products p ON pt.product_id = p.id
            LEFT JOIN tags t ON pt.tag_id = t.id
            ORDER BY p.name, t.name 
            LIMIT :l OFFSET :o";
    $s=$pdo->prepare($sql);
    $s->bindValue(':l',$limit,PDO::PARAM_INT);
    $s->bindValue(':o',$offset,PDO::PARAM_INT);
    $s->execute();
    $rows=$s->fetchAll();
    
    echo json_encode(['success'=>true,'data'=>$rows,'pagination'=>[
      'page'=>$page,'limit'=>$limit,'total'=>$total,'pages'=>(int)ceil($total/$limit)
    ]]); exit;
  } catch (Exception $e) {
    http_response_code(500);
    echo json_encode(['error'=>'Database error: ' . $e->getMessage()]);
    exit;
  }
}

if (isset($_GET['product_id'])) {
  $pid=(int)$_GET['product_id'];
  $q=$pdo->prepare("SELECT t.* FROM tags t JOIN product_tags pt ON pt.tag_id=t.id WHERE pt.product_id=:pid ORDER BY t.name");
  $q->execute([':pid'=>$pid]);
  echo json_encode(['success'=>true,'data'=>$q->fetchAll()]); exit;
}

$tag_id = isset($_GET['tag_id']) ? (int)$_GET['tag_id'] : 0;
$tag_slug = !empty($_GET['tag_slug']) ? trim($_GET['tag_slug']) : null;
if (!$tag_id && !$tag_slug) { http_response_code(400); echo json_encode(['error'=>'Provide tag_id or tag_slug']); exit; }

if ($tag_slug) {
  $r=$pdo->prepare("SELECT id FROM tags WHERE slug=:slug"); $r->execute([':slug'=>$tag_slug]);
  $tag_id=(int)$r->fetchColumn();
  if(!$tag_id){ http_response_code(404); echo json_encode(['error'=>'Tag not found']); exit; }
}

$page=max(1,(int)($_GET['page']??1)); $limit=min(100,max(1,(int)($_GET['limit']??20))); $offset=($page-1)*$limit;

$count=$pdo->prepare("SELECT COUNT(*) FROM product_tags WHERE tag_id=:tid");
$count->execute([':tid'=>$tag_id]); $total=(int)$count->fetchColumn();

$sql="SELECT p.*,
 (SELECT image_url FROM product_images WHERE product_id=p.id AND is_primary=1 LIMIT 1) AS primary_image_url
 FROM products p
 JOIN product_tags pt ON pt.product_id=p.id
 WHERE pt.tag_id=:tid AND p.is_active=1
 ORDER BY p.created_at DESC
 LIMIT :l OFFSET :o";
$s=$pdo->prepare($sql);
$s->bindValue(':tid',$tag_id,PDO::PARAM_INT);
$s->bindValue(':l',$limit,PDO::PARAM_INT);
$s->bindValue(':o',$offset,PDO::PARAM_INT);
$s->execute();
$rows=$s->fetchAll();

echo json_encode(['success'=>true,'data'=>$rows,'pagination'=>[
  'page'=>$page,'limit'=>$limit,'total'=>$total,'pages'=>(int)ceil($total/$limit)
]]);