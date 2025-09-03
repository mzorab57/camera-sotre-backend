<?php
require_once __DIR__ . '/../config/cors.php';
require_once __DIR__ . '/../config/db.php';
require_once __DIR__ . '/../middleware/protect_admin.php';

if ($_SERVER['REQUEST_METHOD'] !== 'GET') {
    http_response_code(405);
    echo json_encode(['error' => 'Method not allowed']);
    exit;
}

$pdo = db();

if (isset($_GET['id'])) {
    $id = (int)$_GET['id'];
    if ($id <= 0) {
        http_response_code(422);
        echo json_encode(['error' => 'Invalid id']);
        exit;
    }
    $stmt = $pdo->prepare("SELECT id, full_name, email, phone, role, is_active, last_login_at, created_at, updated_at FROM users WHERE id = :id");
    $stmt->execute([':id' => $id]);
    $user = $stmt->fetch();
    if (!$user) {
        http_response_code(404);
        echo json_encode(['error' => 'User not found']);
        exit;
    }
    echo json_encode(['success' => true, 'data' => $user]);
    exit;
}

$page  = max(1, (int)($_GET['page'] ?? 1));
$limit = min(100, max(1, (int)($_GET['limit'] ?? 20)));
$offset = ($page - 1) * $limit;

$where = [];
$params = [];

if (isset($_GET['role']) && in_array($_GET['role'], ['admin','employee'], true)) {
    $where[] = 'role = :role';
    $params[':role'] = $_GET['role'];
}
if (isset($_GET['is_active'])) {
    $where[] = 'is_active = :is_active';
    $params[':is_active'] = (int)!!$_GET['is_active'];
}
if (!empty($_GET['q'])) {
    $q = '%' . trim($_GET['q']) . '%';
    $where[] = '(full_name LIKE :q OR email LIKE :q OR phone LIKE :q)';
    $params[':q'] = $q;
}

$whereSql = $where ? ('WHERE ' . implode(' AND ', $where)) : '';

$countSql = "SELECT COUNT(*) AS total FROM users $whereSql";
$stmt = $pdo->prepare($countSql);
$stmt->execute($params);
$total = (int)$stmt->fetchColumn();

$sql = "SELECT id, full_name, email, phone, role, is_active, last_login_at, created_at, updated_at
        FROM users
        $whereSql
        ORDER BY created_at DESC
        LIMIT :limit OFFSET :offset";
$stmt = $pdo->prepare($sql);
foreach ($params as $k => $v) $stmt->bindValue($k, $v);
$stmt->bindValue(':limit', $limit, PDO::PARAM_INT);
$stmt->bindValue(':offset', $offset, PDO::PARAM_INT);
$stmt->execute();
$rows = $stmt->fetchAll();

echo json_encode([
    'success' => true,
    'data' => $rows,
    'pagination' => [
        'page' => $page,
        'limit' => $limit,
        'total' => $total,
        'pages' => (int)ceil($total / $limit)
    ]
]);