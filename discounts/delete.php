<?php
require_once '../config/cors.php';
require_once '../config/db.php';

if ($_SERVER['REQUEST_METHOD'] !== 'DELETE') {
    http_response_code(405);
    echo json_encode(['success' => false, 'error' => 'Method not allowed']);
    exit();
}

$pdo = db();

try {
    
    // Get discount ID from URL parameter
    $discount_id = $_GET['id'] ?? null;
    
    if (!$discount_id) {
        throw new Exception('Discount ID is required');
    }
    
    if (!is_numeric($discount_id)) {
        throw new Exception('Invalid discount ID');
    }
    
    // Check if discount exists
    $check_sql = "SELECT id, name FROM discounts WHERE id = :id";
    $check_stmt = $pdo->prepare($check_sql);
    $check_stmt->execute([':id' => $discount_id]);
    $discount = $check_stmt->fetch(PDO::FETCH_ASSOC);
    
    if (!$discount) {
        http_response_code(404);
        echo json_encode([
            'success' => false,
            'error' => 'Discount not found'
        ]);
        exit;
    }
    
    // Delete the discount
    $delete_sql = "DELETE FROM discounts WHERE id = :id";
    $delete_stmt = $pdo->prepare($delete_sql);
    $delete_stmt->execute([':id' => $discount_id]);
    
    if ($delete_stmt->rowCount() > 0) {
        echo json_encode([
            'success' => true,
            'message' => 'Discount deleted successfully',
            'data' => [
                'id' => $discount_id,
                'name' => $discount['name']
            ]
        ]);
    } else {
        http_response_code(500);
        echo json_encode([
            'success' => false,
            'error' => 'Failed to delete discount'
        ]);
    }
    
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