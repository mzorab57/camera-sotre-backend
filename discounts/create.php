<?php
require_once '../config/cors.php';
require_once '../config/db.php';

if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
    http_response_code(405);
    echo json_encode(['success' => false, 'error' => 'Method not allowed']);
    exit();
}

try {
    $pdo = db();
    
    $input = json_decode(file_get_contents('php://input'), true);
    
    // Validate required fields
    $required_fields = ['name', 'discount_type', 'discount_value', 'target_type', 'target_id', 'start_date'];
    foreach ($required_fields as $field) {
        if (!isset($input[$field]) || empty($input[$field])) {
            throw new Exception("Field '$field' is required");
        }
    }
    
    // Validate discount_type
    if (!in_array($input['discount_type'], ['percentage', 'fixed_amount'])) {
        throw new Exception('Invalid discount_type. Must be percentage or fixed_amount');
    }
    
    // Validate target_type
    if (!in_array($input['target_type'], ['product', 'category', 'subcategory'])) {
        throw new Exception('Invalid target_type. Must be product, category, or subcategory');
    }
    
    // Validate discount_value
    if ($input['discount_type'] === 'percentage' && ($input['discount_value'] < 0 || $input['discount_value'] > 100)) {
        throw new Exception('Percentage discount must be between 0 and 100');
    }
    
    if ($input['discount_value'] <= 0) {
        throw new Exception('Discount value must be greater than 0');
    }
    
    $sql = "INSERT INTO discounts (
        name, description, discount_type, discount_value, target_type, target_id,
        start_date, end_date, is_active, priority, max_uses, min_order_amount
    ) VALUES (
        :name, :description, :discount_type, :discount_value, :target_type, :target_id,
        :start_date, :end_date, :is_active, :priority, :max_uses, :min_order_amount
    )";
    
    $stmt = $pdo->prepare($sql);
    
    $params = [
        ':name' => $input['name'],
        ':description' => $input['description'] ?? null,
        ':discount_type' => $input['discount_type'],
        ':discount_value' => $input['discount_value'],
        ':target_type' => $input['target_type'],
        ':target_id' => $input['target_id'],
        ':start_date' => $input['start_date'],
        ':end_date' => $input['end_date'] ?? null,
        ':is_active' => isset($input['is_active']) ? (bool)$input['is_active'] : true,
        ':priority' => $input['priority'] ?? 0,
        ':max_uses' => $input['max_uses'] ?? null,
        ':min_order_amount' => $input['min_order_amount'] ?? null
    ];
    
    $stmt->execute($params);
    
    $discount_id = $pdo->lastInsertId();
    
    // Fetch the created discount
    $fetch_sql = "SELECT * FROM discounts WHERE id = :id";
    $fetch_stmt = $pdo->prepare($fetch_sql);
    $fetch_stmt->execute([':id' => $discount_id]);
    $discount = $fetch_stmt->fetch(PDO::FETCH_ASSOC);
    
    echo json_encode([
        'success' => true,
        'message' => 'Discount created successfully',
        'data' => $discount
    ]);
    
} catch (PDOException $e) {
    http_response_code(500);
    echo json_encode([
        'success' => false,
        'error' => 'Database error: ' . $e->getMessage()
    ]);
} catch (Exception $e) {
    http_response_code(400);
    echo json_encode([
        'success' => false,
        'error' => $e->getMessage()
    ]);
}
?>