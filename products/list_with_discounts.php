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
    
    // Pagination
    $page = max(1, (int)($_GET['page'] ?? 1));
    $limit = min(100, max(1, (int)($_GET['limit'] ?? 20)));
    $offset = ($page - 1) * $limit;
    
    // Filters
    $where = ['p.is_active = 1'];
    $params = [];
    
    if (!empty($_GET['category_id'])) {
        $where[] = '(p.category_id = :category_id OR s.category_id = :category_id)';
        $params[':category_id'] = (int)$_GET['category_id'];
    }
    
    if (!empty($_GET['subcategory_id'])) {
        $where[] = 'p.subcategory_id = :subcategory_id';
        $params[':subcategory_id'] = (int)$_GET['subcategory_id'];
    }
    
    if (!empty($_GET['brand'])) {
        $where[] = 'p.brand = :brand';
        $params[':brand'] = $_GET['brand'];
    }
    
    if (!empty($_GET['type'])) {
        $where[] = 'p.type = :type';
        $params[':type'] = $_GET['type'];
    }
    
    if (isset($_GET['is_featured'])) {
        $where[] = 'p.is_featured = :is_featured';
        $params[':is_featured'] = (int)!!$_GET['is_featured'];
    }
    
    if (!empty($_GET['min_price'])) {
        $where[] = 'p.price >= :min_price';
        $params[':min_price'] = (float)$_GET['min_price'];
    }
    
    if (!empty($_GET['max_price'])) {
        $where[] = 'p.price <= :max_price';
        $params[':max_price'] = (float)$_GET['max_price'];
    }
    
    if (!empty($_GET['search'])) {
        $where[] = '(p.name LIKE :search OR p.model LIKE :search OR p.sku LIKE :search OR p.description LIKE :search)';
        $params[':search'] = '%' . trim($_GET['search']) . '%';
    }
    
    // Show only products with discounts
    $showOnlyDiscounted = isset($_GET['discounted_only']) && $_GET['discounted_only'];
    
    $whereClause = 'WHERE ' . implode(' AND ', $where);
    
    // Count total products
    $countSql = "
        SELECT COUNT(DISTINCT p.id) 
        FROM products p
        LEFT JOIN subcategories s ON p.subcategory_id = s.id
        LEFT JOIN categories c ON COALESCE(p.category_id, s.category_id) = c.id
        $whereClause
    ";
    $countStmt = $pdo->prepare($countSql);
    $countStmt->execute($params);
    $totalProducts = (int)$countStmt->fetchColumn();
    
    // Get products with category and subcategory information
    $sql = "
        SELECT p.id, p.name, p.model, p.slug, p.sku, p.description, p.short_description,
               p.price, p.discount_price, p.type, p.brand, p.is_featured, p.is_active,
               p.subcategory_id, p.category_id,
               s.name as subcategory_name, s.category_id as subcategory_category_id,
               c.name as category_name,
               pi.image_url as primary_image
        FROM products p
        LEFT JOIN subcategories s ON p.subcategory_id = s.id
        LEFT JOIN categories c ON COALESCE(p.category_id, s.category_id) = c.id
        LEFT JOIN (
            SELECT product_id, image_url, 
                   ROW_NUMBER() OVER (PARTITION BY product_id ORDER BY is_primary DESC, id ASC) as rn
            FROM product_images
        ) pi ON p.id = pi.product_id AND pi.rn = 1
        $whereClause
        ORDER BY p.is_featured DESC, p.created_at DESC
        LIMIT :limit OFFSET :offset
    ";
    
    $stmt = $pdo->prepare($sql);
    foreach ($params as $key => $value) {
        $stmt->bindValue($key, $value);
    }
    $stmt->bindValue(':limit', $limit, PDO::PARAM_INT);
    $stmt->bindValue(':offset', $offset, PDO::PARAM_INT);
    $stmt->execute();
    $products = $stmt->fetchAll();
    
    // Calculate discounts for each product
    $productsWithDiscounts = [];
    $discountedCount = 0;
    
    foreach ($products as $product) {
        $categoryId = $product['category_id'] ?: $product['subcategory_category_id'];
        $discount = calculateProductDiscount($product['id'], $product['subcategory_id'], $categoryId);
        
        $originalPrice = (float)$product['price'];
        $finalPrice = $discount ? applyDiscount($originalPrice, $discount) : $originalPrice;
        $discountAmount = $originalPrice - $finalPrice;
        $discountPercentage = $originalPrice > 0 ? ($discountAmount / $originalPrice) * 100 : 0;
        
        $hasDiscount = $discount !== null && $discountAmount > 0;
        if ($hasDiscount) {
            $discountedCount++;
        }
        
        // Skip if showing only discounted products and this product has no discount
        if ($showOnlyDiscounted && !$hasDiscount) {
            continue;
        }
        
        $productData = [
            'id' => $product['id'],
            'name' => $product['name'],
            'model' => $product['model'],
            'slug' => $product['slug'],
            'sku' => $product['sku'],
            'description' => $product['description'],
            'short_description' => $product['short_description'],
            'type' => $product['type'],
            'brand' => $product['brand'],
            'is_featured' => (bool)$product['is_featured'],
            'primary_image' => $product['primary_image'],
            'category' => [
                'id' => $categoryId,
                'name' => $product['category_name']
            ],
            'subcategory' => [
                'id' => $product['subcategory_id'],
                'name' => $product['subcategory_name']
            ],
            'pricing' => [
                'original_price' => $originalPrice,
                'final_price' => $finalPrice,
                'discount_amount' => $discountAmount,
                'discount_percentage' => round($discountPercentage, 2),
                'has_discount' => $hasDiscount
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
                'end_date' => $discount['end_date'],
                'inheritance_level' => (
                    $discount['target_type'] === 'product' ? 'direct' :
                    ($discount['target_type'] === 'subcategory' ? 'subcategory' : 'category')
                )
            ] : null
        ];
        
        $productsWithDiscounts[] = $productData;
    }
    
    // Adjust total count if filtering by discounted only
    if ($showOnlyDiscounted) {
        $totalProducts = count($productsWithDiscounts);
    }
    
    $totalPages = ceil($totalProducts / $limit);
    
    // Get discount statistics
    $statsStmt = $pdo->prepare("
        SELECT 
            COUNT(*) as total_active_discounts,
            SUM(CASE WHEN target_type = 'product' THEN 1 ELSE 0 END) as product_discounts,
            SUM(CASE WHEN target_type = 'subcategory' THEN 1 ELSE 0 END) as subcategory_discounts,
            SUM(CASE WHEN target_type = 'category' THEN 1 ELSE 0 END) as category_discounts
        FROM discounts 
        WHERE is_active = 1 
        AND start_date <= NOW() 
        AND (end_date IS NULL OR end_date >= NOW())
    ");
    $statsStmt->execute();
    $discountStats = $statsStmt->fetch();
    
    $response = [
        'success' => true,
        'data' => $productsWithDiscounts,
        'pagination' => [
            'current_page' => $page,
            'total_pages' => $totalPages,
            'total_items' => $totalProducts,
            'items_per_page' => $limit,
            'has_next' => $page < $totalPages,
            'has_prev' => $page > 1
        ],
        'statistics' => [
            'total_products' => count($productsWithDiscounts),
            'discounted_products' => $discountedCount,
            'discount_coverage' => count($productsWithDiscounts) > 0 ? 
                round(($discountedCount / count($productsWithDiscounts)) * 100, 2) : 0,
            'active_discounts' => [
                'total' => (int)$discountStats['total_active_discounts'],
                'product_level' => (int)$discountStats['product_discounts'],
                'subcategory_level' => (int)$discountStats['subcategory_discounts'],
                'category_level' => (int)$discountStats['category_discounts']
            ]
        ],
        'filters_applied' => [
            'category_id' => $_GET['category_id'] ?? null,
            'subcategory_id' => $_GET['subcategory_id'] ?? null,
            'brand' => $_GET['brand'] ?? null,
            'type' => $_GET['type'] ?? null,
            'is_featured' => isset($_GET['is_featured']) ? (bool)$_GET['is_featured'] : null,
            'price_range' => [
                'min' => $_GET['min_price'] ?? null,
                'max' => $_GET['max_price'] ?? null
            ],
            'search' => $_GET['search'] ?? null,
            'discounted_only' => $showOnlyDiscounted
        ]
    ];
    
    echo json_encode($response);
    
} catch (Exception $e) {
    http_response_code(400);
    echo json_encode([
        'success' => false,
        'error' => $e->getMessage()
    ]);
}
?>