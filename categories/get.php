<?php
// categories/get.php
require_once __DIR__ . '/../config/cors.php';
require_once __DIR__ . '/../config/db.php';

// Only allow GET requests
if ($_SERVER['REQUEST_METHOD'] !== 'GET') {
    http_response_code(405);
    echo json_encode(['error' => 'Method not allowed']);
    exit;
}

try {
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

    // Build WHERE clause and parameters
    $whereConditions = [];
    $params = [];
    
    // Search functionality
    if (!empty($_GET['q'])) {
        $searchTerm = trim($_GET['q']);
        if (!empty($searchTerm)) {
            $whereConditions[] = "(name LIKE :search OR slug LIKE :search)";
            $params[':search'] = '%' . $searchTerm . '%';
        }
    }
    
    // Filter by active status
    if (isset($_GET['is_active'])) {
        $isActive = $_GET['is_active'];
        if ($isActive === '1' || $isActive === 'true') {
            $whereConditions[] = "is_active = 1";
        } elseif ($isActive === '0' || $isActive === 'false') {
            $whereConditions[] = "is_active = 0";
        }
    }
    
    $whereClause = !empty($whereConditions) ? 'WHERE ' . implode(' AND ', $whereConditions) : '';
    
    // Get total count
    $countSql = "SELECT COUNT(*) FROM categories $whereClause";
    $countStmt = $pdo->prepare($countSql);
    $countStmt->execute($params);
    $total = (int)$countStmt->fetchColumn();
    
    // Get categories with pagination
    $sql = "SELECT 
                id, 
                name, 
                slug, 
                image_url, 
                is_active, 
                created_at, 
                updated_at 
            FROM categories 
            $whereClause 
            ORDER BY created_at DESC 
            LIMIT :limit OFFSET :offset";
    
    $stmt = $pdo->prepare($sql);
    
    // Bind search and filter parameters
    foreach ($params as $key => $value) {
        $stmt->bindValue($key, $value);
    }
    
    // Bind pagination parameters
    $stmt->bindValue(':limit', $limit, PDO::PARAM_INT);
    $stmt->bindValue(':offset', $offset, PDO::PARAM_INT);
    
    $stmt->execute();
    $categories = $stmt->fetchAll(PDO::FETCH_ASSOC);
    
    // Calculate pagination info
    $totalPages = (int)ceil($total / $limit);
    
    $response = [
        'success' => true,
        'data' => $categories,
        'pagination' => [
            'page' => $page,
            'limit' => $limit,
            'total' => $total,
            'pages' => $totalPages,
            'has_next' => $page < $totalPages,
            'has_prev' => $page > 1
        ]
    ];
    
    echo json_encode($response);
    
} catch (PDOException $e) {
    error_log("Database error in categories/get.php: " . $e->getMessage());
    http_response_code(500);
    echo json_encode(['error' => 'Database error occurred']);
} catch (Exception $e) {
    error_log("General error in categories/get.php: " . $e->getMessage());
    http_response_code(500);
    echo json_encode(['error' => 'An error occurred']);
}
?>