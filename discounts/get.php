<?php
require_once '../config/cors.php';
require_once '../config/db.php';

if ($_SERVER['REQUEST_METHOD'] !== 'GET') {
    http_response_code(405);
    echo json_encode(['success' => false, 'error' => 'Method not allowed']);
    exit();
}

$pdo = db();

// Get discount by ID
if (isset($_GET['id'])) {
    $id = (int)$_GET['id'];
    $stmt = $pdo->prepare("SELECT d.*, 
        CASE 
            WHEN d.target_type = 'product' THEN p.name
            WHEN d.target_type = 'category' THEN c.name
            WHEN d.target_type = 'subcategory' THEN s.name
        END as target_name
        FROM discounts d
        LEFT JOIN products p ON d.target_type = 'product' AND d.target_id = p.id
        LEFT JOIN categories c ON d.target_type = 'category' AND d.target_id = c.id
        LEFT JOIN subcategories s ON d.target_type = 'subcategory' AND d.target_id = s.id
        WHERE d.id = :id");
    $stmt->execute([':id' => $id]);
    $row = $stmt->fetch();
    if (!$row) {
        http_response_code(404);
        echo json_encode(['error' => 'Not found']);
        exit;
    }
    echo json_encode(['success' => true, 'data' => $row]);
    exit;
}

// List discounts with pagination
$page = max(1, (int)($_GET['page'] ?? 1));
$limit = min(100, max(1, (int)($_GET['limit'] ?? 20)));
$offset = ($page - 1) * $limit;

$where = [];
$params = [];

// Filters
if (!empty($_GET['target_type'])) {
    $where[] = 'd.target_type = :target_type';
    $params[':target_type'] = $_GET['target_type'];
}

if (isset($_GET['is_active'])) {
    $where[] = 'd.is_active = :is_active';
    $params[':is_active'] = (int)!!$_GET['is_active'];
}

if (!empty($_GET['q'])) {
    $where[] = 'd.name LIKE :search';
    $params[':search'] = '%' . trim($_GET['q']) . '%';
}

$whereClause = $where ? 'WHERE ' . implode(' AND ', $where) : '';

// Count total
$countStmt = $pdo->prepare("SELECT COUNT(*) FROM discounts d $whereClause");
$countStmt->execute($params);
$total = (int)$countStmt->fetchColumn();

// Get data
$sql = "SELECT d.*, 
    CASE 
        WHEN d.target_type = 'product' THEN p.name
        WHEN d.target_type = 'category' THEN c.name
        WHEN d.target_type = 'subcategory' THEN s.name
    END as target_name
    FROM discounts d
    LEFT JOIN products p ON d.target_type = 'product' AND d.target_id = p.id
    LEFT JOIN categories c ON d.target_type = 'category' AND d.target_id = c.id
    LEFT JOIN subcategories s ON d.target_type = 'subcategory' AND d.target_id = s.id
    $whereClause
    ORDER BY d.priority DESC, d.created_at DESC
    LIMIT :limit OFFSET :offset";

$stmt = $pdo->prepare($sql);
foreach ($params as $key => $value) {
    $stmt->bindValue($key, $value);
}
$stmt->bindValue(':limit', $limit, PDO::PARAM_INT);
$stmt->bindValue(':offset', $offset, PDO::PARAM_INT);
$stmt->execute();

$discounts = $stmt->fetchAll();

echo json_encode([
    'success' => true,
    'data' => $discounts,
    'pagination' => [
        'page' => $page,
        'limit' => $limit,
        'total' => $total,
        'pages' => (int)ceil($total / $limit)
    ]
]);
?>