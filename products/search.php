<?php
require_once __DIR__ . '/../config/cors.php';
require_once __DIR__ . '/../config/db.php';

header('Content-Type: application/json; charset=utf-8');

function hasFulltext(PDO $pdo): bool {
  try {
    $stmt = $pdo->query("SHOW INDEX FROM products WHERE Index_type='FULLTEXT'");
    return (bool)$stmt->fetch();
  } catch (Throwable $t) {
    return false;
  }
}

if (($_SERVER['REQUEST_METHOD'] ?? '') !== 'GET') {
  http_response_code(405);
  echo json_encode(['error' => 'Method not allowed']); exit;
}

$q = trim($_GET['q'] ?? '');
// Allow empty search query if filters are provided
if ($q === '' && empty($_GET['type']) && empty($_GET['brand']) && !isset($_GET['min_price']) && !isset($_GET['max_price'])) {
  http_response_code(400);
  echo json_encode(['error' => 'Search query or filters are required']); exit;
}

$pdo = db();

try {
  // Filters
  $where = [];
  $params = [];

  if (!empty($_GET['type']) && in_array($_GET['type'], ['videography','photography','both'], true)) {
    $where[] = 'p.type = :type';
    $params[':type'] = $_GET['type'];
  }
  if (!empty($_GET['brand'])) {
    $where[] = 'p.brand = :brand';
    $params[':brand'] = trim($_GET['brand']);
  }
  if (isset($_GET['min_price'])) { $where[] = 'p.price >= :minp'; $params[':minp'] = (float)$_GET['min_price']; }
  if (isset($_GET['max_price'])) { $where[] = 'p.price <= :maxp'; $params[':maxp'] = (float)$_GET['max_price']; }
  if (isset($_GET['is_active'])) {
    $ia = $_GET['is_active'];
    if ($ia === 'all') {
      // no filter
    } else {
      $where[] = 'p.is_active = :ia';
      $params[':ia'] = (int)!!$ia;
    }
  } else {
    $where[] = 'p.is_active = 1';
  }

  $page  = max(1, (int)($_GET['page'] ?? 1));
  $limit = min(1000, max(1, (int)($_GET['limit'] ?? 20)));
  $offset = ($page - 1) * $limit;

  $w = $where ? ('WHERE ' . implode(' AND ', $where)) : 'WHERE 1=1';

  // Handle search query
  $hasSearchQuery = !empty($q) && $q !== '*';
  $qBool = '';
  
  if ($hasSearchQuery) {
    // Boolean Mode query string (+term)
    $terms = preg_split('/\s+/', $q, -1, PREG_SPLIT_NO_EMPTY);
    $terms = array_slice($terms, 0, 10);
    $qBool = '+' . implode(' +', array_map('trim', $terms));
  }

  $minToken = 4;
  try {
    $stmt1 = $pdo->query("SHOW VARIABLES LIKE 'innodb_ft_min_token_size'");
    $row1 = $stmt1 ? $stmt1->fetch() : false;
    if ($row1 && isset($row1['Value'])) { $minToken = (int)$row1['Value']; }
    else {
      $stmt2 = $pdo->query("SHOW VARIABLES LIKE 'ft_min_word_len'");
      $row2 = $stmt2 ? $stmt2->fetch() : false;
      if ($row2 && isset($row2['Value'])) { $minToken = (int)$row2['Value']; }
    }
  } catch (Throwable $t) { }

  $shortTerm = false;
  if ($hasSearchQuery) {
    foreach ($terms as $t) {
      if (mb_strlen($t) < $minToken) { $shortTerm = true; break; }
    }
  }

  $useFulltext = hasFulltext($pdo) && empty($_GET['like']) && $hasSearchQuery && !$shortTerm;

  if ($useFulltext) {
    // Count
    $countSql = "SELECT COUNT(*) FROM products p $w AND MATCH(p.name,p.model,p.brand,p.short_description,p.description) AGAINST (:qc IN BOOLEAN MODE)";
    $count = $pdo->prepare($countSql);
    foreach ($params as $k=>$v) $count->bindValue($k, $v);
    $count->bindValue(':qc', $qBool, PDO::PARAM_STR);
    $count->execute();
    $total = (int)$count->fetchColumn();

    // Data (note: use q1 and q2, not the same placeholder twice)
    $sql = "SELECT p.*,
      (SELECT image_url FROM product_images WHERE product_id=p.id AND is_primary=1 LIMIT 1) AS primary_image_url
      FROM products p
      $w AND MATCH(p.name,p.model,p.brand,p.short_description,p.description) AGAINST (:q1 IN BOOLEAN MODE)
      ORDER BY MATCH(p.name,p.model,p.brand,p.short_description,p.description) AGAINST (:q2 IN BOOLEAN MODE) DESC, p.created_at DESC
      LIMIT :l OFFSET :o";

    $s = $pdo->prepare($sql);
    foreach ($params as $k=>$v) $s->bindValue($k, $v);
    $s->bindValue(':q1', $qBool, PDO::PARAM_STR);
    $s->bindValue(':q2', $qBool, PDO::PARAM_STR);
    $s->bindValue(':l', $limit, PDO::PARAM_INT);
    $s->bindValue(':o', $offset, PDO::PARAM_INT);
    $s->execute();
    $rows = $s->fetchAll();

  } else {
    // LIKE fallback or filter-only search
    if ($hasSearchQuery) {
      // Split into tokens and require all tokens to match in any of the fields
      $tokens = preg_split('/[\s\-]+/', $q, -1, PREG_SPLIT_NO_EMPTY);
      $tokens = array_slice($tokens, 0, 10);
      if (empty($tokens)) { $tokens = [$q]; }
 
      $likeWheres = [];
      $likeParams = [];
      $i = 0;
      foreach ($tokens as $t) {
        $i++;
        $ph = ":t{$i}";
        $likeParams[$ph] = '%' . $t . '%';
        $likeWheres[] = "(p.name LIKE {$ph} OR p.model LIKE {$ph} OR p.brand LIKE {$ph} OR p.short_description LIKE {$ph} OR p.description LIKE {$ph})";
      }
      $likeClause = implode(' AND ', $likeWheres);
 
      $countSql = "SELECT COUNT(*) FROM products p $w AND ($likeClause)";
      $count = $pdo->prepare($countSql);
      foreach ($params as $k=>$v) $count->bindValue($k, $v);
      foreach ($likeParams as $k=>$v) $count->bindValue($k, $v, PDO::PARAM_STR);
      $count->execute();
      $total = (int)$count->fetchColumn();
 
      $sql = "SELECT p.*,
        (SELECT image_url FROM product_images WHERE product_id=p.id AND is_primary=1 LIMIT 1) AS primary_image_url
        FROM products p
        $w AND ($likeClause)
        ORDER BY p.created_at DESC
        LIMIT :l OFFSET :o";
      $s = $pdo->prepare($sql);
      foreach ($params as $k=>$v) $s->bindValue($k, $v);
      foreach ($likeParams as $k=>$v) $s->bindValue($k, $v, PDO::PARAM_STR);
      $s->bindValue(':l', $limit, PDO::PARAM_INT);
      $s->bindValue(':o', $offset, PDO::PARAM_INT);
      $s->execute();
      $rows = $s->fetchAll();
    } else {
      // Filter-only search (no search query)
      $countSql = "SELECT COUNT(*) FROM products p $w";
      $count = $pdo->prepare($countSql);
      foreach ($params as $k=>$v) $count->bindValue($k, $v);
      $count->execute();
      $total = (int)$count->fetchColumn();

      $sql = "SELECT p.*,
        (SELECT image_url FROM product_images WHERE product_id=p.id AND is_primary=1 LIMIT 1) AS primary_image_url
        FROM products p
        $w
        ORDER BY p.created_at DESC
        LIMIT :l OFFSET :o";
      $s = $pdo->prepare($sql);
      foreach ($params as $k=>$v) $s->bindValue($k, $v);
      $s->bindValue(':l', $limit, PDO::PARAM_INT);
      $s->bindValue(':o', $offset, PDO::PARAM_INT);
      $s->execute();
      $rows = $s->fetchAll();
    }
  }

  echo json_encode([
    'success' => true,
    'data' => $rows,
    'pagination' => [
      'page' => $page,
      'limit' => $limit,
      'total' => $total,
      'pages' => (int)ceil($total / $limit)
    ],
    'mode' => $useFulltext ? 'fulltext' : 'like'
  ]);

} catch (PDOException $e) {
  // لە دیڤ: APP_DEBUG=1 بکە لە .env تا وردەکاری ببینیت
  $debug = !empty($_ENV['APP_DEBUG']) && $_ENV['APP_DEBUG'] === '1';
  http_response_code(500);
  echo json_encode(['error'=>'Database error', 'details' => $debug ? $e->getMessage() : null]);
} catch (Throwable $t) {
  $debug = !empty($_ENV['APP_DEBUG']) && $_ENV['APP_DEBUG'] === '1';
  http_response_code(500);
  echo json_encode(['error'=>'Internal error', 'details' => $debug ? $t->getMessage() : null]);
}
