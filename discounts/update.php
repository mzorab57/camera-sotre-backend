<?php
require_once '../config/cors.php';
require_once '../config/db.php';

if ($_SERVER['REQUEST_METHOD'] !== 'PUT') {
    http_response_code(405);
    echo json_encode(['success' => false, 'error' => 'Method not allowed']);
    exit();
}

$pdo = db();

try {
    
    $input = json_decode(file_get_contents('php://input'), true);
    
    // Get discount ID from URL parameter
    $discount_id = $_GET['id'] ?? null;
    
    if (!$discount_id) {
        throw new Exception('Discount ID is required');
    }
    
    if (!is_numeric($discount_id)) {
        throw new Exception('Invalid discount ID');
    }
    
    // Check if discount exists
    $check_sql = "SELECT * FROM discounts WHERE id = :id";
    $check_stmt = $pdo->prepare($check_sql);
    $check_stmt->execute([':id' => $discount_id]);
    $existing_discount = $check_stmt->fetch(PDO::FETCH_ASSOC);
    
    if (!$existing_discount) {
        http_response_code(404);
        echo json_encode([
            'success' => false,
            'error' => 'Discount not found'
        ]);
        exit;
    }
    
    // Validate fields if provided
    if (isset($input['discount_type']) && !in_array($input['discount_type'], ['percentage', 'fixed_amount'])) {
        throw new Exception('Invalid discount_type. Must be percentage or fixed_amount');
    }
    
    if (isset($input['target_type']) && !in_array($input['target_type'], ['product', 'category', 'subcategory'])) {
        throw new Exception('Invalid target_type. Must be product, category, or subcategory');
    }
    
    // Validate discount_value if provided
    if (isset($input['discount_value'])) {
        $discount_type = $input['discount_type'] ?? $existing_discount['discount_type'];
        if ($discount_type === 'percentage' && ($input['discount_value'] < 0 || $input['discount_value'] > 100)) {
            throw new Exception('Percentage discount must be between 0 and 100');
        }
        
        if ($input['discount_value'] <= 0) {
            throw new Exception('Discount value must be greater than 0');
        }
    }
    
    // Build update query dynamically
    $update_fields = [];
    $params = [':id' => $discount_id];
    
    $allowed_fields = [
        'name', 'description', 'discount_type', 'discount_value', 'target_type', 'target_id',
        'start_date', 'end_date', 'is_active', 'priority', 'max_uses', 'min_order_amount'
    ];
    
    foreach ($allowed_fields as $field) {
        if (isset($input[$field])) {
            $update_fields[] = "$field = :$field";
            $params[":$field"] = $input[$field];
        }
    }
    
    if (empty($update_fields)) {
        throw new Exception('No valid fields provided for update');
    }
    
    $sql = "UPDATE discounts SET " . implode(', ', $update_fields) . " WHERE id = :id";
    
    $stmt = $pdo->prepare($sql);
    $stmt->execute($params);
    
    // Fetch the updated discount
    $fetch_sql = "SELECT * FROM discounts WHERE id = :id";
    $fetch_stmt = $pdo->prepare($fetch_sql);
    $fetch_stmt->execute([':id' => $discount_id]);
    $updated_discount = $fetch_stmt->fetch(PDO::FETCH_ASSOC);
    
    echo json_encode([
        'success' => true,
        'message' => 'Discount updated successfully',
        'data' => $updated_discount
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