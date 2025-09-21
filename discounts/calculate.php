<?php
require_once '../config/cors.php';
require_once '../config/db.php';
require_once '../utils/discount_calculator.php';

if ($_SERVER['REQUEST_METHOD'] !== 'GET') {
    http_response_code(405);
    echo json_encode(['success' => false, 'error' => 'Method not allowed']);
    exit();
}

try {
    $pdo = db();
    
    // Get product information with category and subcategory
    if (isset($_GET['product_id'])) {
        $productId = (int)$_GET['product_id'];
        
        // Get product with its category and subcategory information
        $stmt = $pdo->prepare("
            SELECT p.id, p.name, p.price, p.discount_price, p.subcategory_id, p.category_id,
                   s.name as subcategory_name, s.category_id as subcategory_category_id,
                   c.name as category_name
            FROM products p
            LEFT JOIN subcategories s ON p.subcategory_id = s.id
            LEFT JOIN categories c ON COALESCE(p.category_id, s.category_id) = c.id
            WHERE p.id = :product_id AND p.is_active = 1
        ");
        $stmt->execute([':product_id' => $productId]);
        $product = $stmt->fetch();
        
        if (!$product) {
            http_response_code(404);
            echo json_encode(['success' => false, 'error' => 'Product not found']);
            exit();
        }
        
        // Use the category_id from product or fallback to subcategory's category_id
        $categoryId = $product['category_id'] ?: $product['subcategory_category_id'];
        
        // Calculate discount using hierarchical approach
        $discount = calculateProductDiscount($productId, $product['subcategory_id'], $categoryId);
        
        $originalPrice = $product['price'];
        $finalPrice = $discount ? applyDiscount($originalPrice, $discount) : $originalPrice;
        
        // Calculate discount amount and percentage
        $discountAmount = $originalPrice - $finalPrice;
        $discountPercentage = $originalPrice > 0 ? ($discountAmount / $originalPrice) * 100 : 0;
        
        $response = [
            'success' => true,
            'data' => [
                'product' => [
                    'id' => $product['id'],
                    'name' => $product['name'],
                    'category_name' => $product['category_name'],
                    'subcategory_name' => $product['subcategory_name']
                ],
                'pricing' => [
                    'original_price' => (float)$originalPrice,
                    'final_price' => (float)$finalPrice,
                    'discount_amount' => (float)$discountAmount,
                    'discount_percentage' => round($discountPercentage, 2)
                ],
                'discount' => $discount ? [
                    'id' => $discount['id'],
                    'name' => $discount['name'],
                    'description' => $discount['description'],
                    'type' => $discount['discount_type'],
                    'value' => (float)$discount['discount_value'],
                    'target_type' => $discount['target_type'],
                    'target_id' => $discount['target_id'],
                    'priority' => $discount['priority'],
                    'start_date' => $discount['start_date'],
                    'end_date' => $discount['end_date']
                ] : null
            ]
        ];
        
        echo json_encode($response);
        exit();
    }
    
    // Bulk discount calculation for multiple products
    if (isset($_GET['product_ids'])) {
        $productIds = explode(',', $_GET['product_ids']);
        $productIds = array_map('intval', $productIds);
        $productIds = array_filter($productIds, function($id) { return $id > 0; });
        
        if (empty($productIds)) {
            throw new Exception('Invalid product IDs provided');
        }
        
        $placeholders = str_repeat('?,', count($productIds) - 1) . '?';
        $stmt = $pdo->prepare("
            SELECT p.id, p.name, p.price, p.discount_price, p.subcategory_id, p.category_id,
                   s.name as subcategory_name, s.category_id as subcategory_category_id,
                   c.name as category_name
            FROM products p
            LEFT JOIN subcategories s ON p.subcategory_id = s.id
            LEFT JOIN categories c ON COALESCE(p.category_id, s.category_id) = c.id
            WHERE p.id IN ($placeholders) AND p.is_active = 1
        ");
        $stmt->execute($productIds);
        $products = $stmt->fetchAll();
        
        $results = [];
        foreach ($products as $product) {
            $categoryId = $product['category_id'] ?: $product['subcategory_category_id'];
            $discount = calculateProductDiscount($product['id'], $product['subcategory_id'], $categoryId);
            
            $originalPrice = $product['price'];
            $finalPrice = $discount ? applyDiscount($originalPrice, $discount) : $originalPrice;
            $discountAmount = $originalPrice - $finalPrice;
            $discountPercentage = $originalPrice > 0 ? ($discountAmount / $originalPrice) * 100 : 0;
            
            $results[] = [
                'product_id' => $product['id'],
                'product_name' => $product['name'],
                'original_price' => (float)$originalPrice,
                'final_price' => (float)$finalPrice,
                'discount_amount' => (float)$discountAmount,
                'discount_percentage' => round($discountPercentage, 2),
                'discount' => $discount ? [
                    'id' => $discount['id'],
                    'name' => $discount['name'],
                    'type' => $discount['discount_type'],
                    'value' => (float)$discount['discount_value'],
                    'target_type' => $discount['target_type']
                ] : null
            ];
        }
        
        echo json_encode([
            'success' => true,
            'data' => $results
        ]);
        exit();
    }
    
    // Get all active discounts with their targets
    $stmt = $pdo->prepare("
        SELECT d.*, 
            CASE 
                WHEN d.target_type = 'product' THEN p.name
                WHEN d.target_type = 'category' THEN c.name
                WHEN d.target_type = 'subcategory' THEN s.name
            END as target_name,
            CASE 
                WHEN d.target_type = 'product' THEN CONCAT('Product: ', p.name)
                WHEN d.target_type = 'category' THEN CONCAT('Category: ', c.name)
                WHEN d.target_type = 'subcategory' THEN CONCAT('Subcategory: ', s.name)
            END as target_display
        FROM discounts d
        LEFT JOIN products p ON d.target_type = 'product' AND d.target_id = p.id
        LEFT JOIN categories c ON d.target_type = 'category' AND d.target_id = c.id
        LEFT JOIN subcategories s ON d.target_type = 'subcategory' AND d.target_id = s.id
        WHERE d.is_active = 1 
        AND d.start_date <= NOW() 
        AND (d.end_date IS NULL OR d.end_date >= NOW())
        ORDER BY d.priority DESC, d.created_at DESC
    ");
    $stmt->execute();
    $discounts = $stmt->fetchAll();
    
    echo json_encode([
        'success' => true,
        'data' => $discounts
    ]);
    
} catch (Exception $e) {
    http_response_code(400);
    echo json_encode([
        'success' => false,
        'error' => $e->getMessage()
    ]);
}
?>