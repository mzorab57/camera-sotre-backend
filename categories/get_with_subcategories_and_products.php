<?php
// categories/get_with_subcategories_and_products.php
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
    
    // Get single category with subcategories and products
    if (isset($_GET['id'])) {
        $categoryId = (int)$_GET['id'];
        
        // Get category
        $categoryStmt = $pdo->prepare("SELECT * FROM categories WHERE id = :id AND is_active = 1");
        $categoryStmt->execute([':id' => $categoryId]);
        $category = $categoryStmt->fetch(PDO::FETCH_ASSOC);
        
        if (!$category) {
            http_response_code(404);
            echo json_encode(['error' => 'Category not found']);
            exit;
        }
        
        // Get subcategories for this category
        $subcategoriesStmt = $pdo->prepare("
            SELECT s.*, 
                   COUNT(p.id) as product_count
            FROM subcategories s 
            LEFT JOIN products p ON s.id = p.subcategory_id AND p.is_active = 1
            WHERE s.category_id = :category_id AND s.is_active = 1
            GROUP BY s.id
            ORDER BY s.created_at DESC
        ");
        $subcategoriesStmt->execute([':category_id' => $categoryId]);
        $subcategories = $subcategoriesStmt->fetchAll(PDO::FETCH_ASSOC);
        
        // Get products for each subcategory
        foreach ($subcategories as &$subcategory) {
            $productsStmt = $pdo->prepare("
                SELECT p.id, p.name, p.slug, p.price, p.type, p.brand, p.is_active, p.short_description, p.description, p.discount_price,
                       (SELECT image_url FROM product_images WHERE product_id = p.id AND is_primary = 1 LIMIT 1) AS primary_image_url
                FROM products p
                WHERE p.subcategory_id = :subcategory_id AND p.is_active = 1
                ORDER BY p.created_at DESC
                LIMIT 10
            ");
            $productsStmt->execute([':subcategory_id' => $subcategory['id']]);
            $products = $productsStmt->fetchAll(PDO::FETCH_ASSOC);
            
            // Get all images for each product
            foreach ($products as &$product) {
                $imagesStmt = $pdo->prepare("
                    SELECT id, image_url, is_primary, display_order
                    FROM product_images 
                    WHERE product_id = :product_id 
                    ORDER BY is_primary DESC, display_order ASC
                ");
                $imagesStmt->execute([':product_id' => $product['id']]);
                $product['images'] = $imagesStmt->fetchAll(PDO::FETCH_ASSOC);
                
                // Get all specifications for each product
                $specificationsStmt = $pdo->prepare("
                    SELECT id, spec_name, spec_value, spec_group, display_order
                    FROM product_specifications 
                    WHERE product_id = :product_id 
                    ORDER BY spec_group IS NULL, spec_group, display_order, spec_name
                ");
                $specificationsStmt->execute([':product_id' => $product['id']]);
                $product['specifications'] = $specificationsStmt->fetchAll(PDO::FETCH_ASSOC);
            }
            
            $subcategory['products'] = $products;
        }
        
        $category['subcategories'] = $subcategories;
        
        echo json_encode([
            'success' => true,
            'data' => $category
        ]);
        exit;
    }
    
    // Get all categories with subcategories and products
    $page = max(1, (int)($_GET['page'] ?? 1));
    $limit = min(50, max(1, (int)($_GET['limit'] ?? 10)));
    $offset = ($page - 1) * $limit;
    
    // Build WHERE clause for categories
    $whereConditions = ['c.is_active = 1'];
    $params = [];
    
    // Search functionality
    if (!empty($_GET['q'])) {
        $searchTerm = trim($_GET['q']);
        if (!empty($searchTerm)) {
            $whereConditions[] = "(c.name LIKE :search OR c.slug LIKE :search)";
            $params[':search'] = '%' . $searchTerm . '%';
        }
    }
    
    $whereClause = 'WHERE ' . implode(' AND ', $whereConditions);
    
    // Get total count
    $countSql = "SELECT COUNT(*) FROM categories c $whereClause";
    $countStmt = $pdo->prepare($countSql);
    $countStmt->execute($params);
    $total = (int)$countStmt->fetchColumn();
    
    // Get categories
    $categoriesStmt = $pdo->prepare("
        SELECT c.*, 
               COUNT(DISTINCT s.id) as subcategory_count,
               COUNT(DISTINCT p.id) as product_count
        FROM categories c
        LEFT JOIN subcategories s ON c.id = s.category_id AND s.is_active = 1
        LEFT JOIN products p ON s.id = p.subcategory_id AND p.is_active = 1
        $whereClause
        GROUP BY c.id
        ORDER BY c.created_at DESC
        LIMIT :limit OFFSET :offset
    ");
    
    // Bind search parameters
    foreach ($params as $key => $value) {
        $categoriesStmt->bindValue($key, $value);
    }
    
    // Bind pagination parameters
    $categoriesStmt->bindValue(':limit', $limit, PDO::PARAM_INT);
    $categoriesStmt->bindValue(':offset', $offset, PDO::PARAM_INT);
    
    $categoriesStmt->execute();
    $categories = $categoriesStmt->fetchAll(PDO::FETCH_ASSOC);
    
    // Get subcategories and products for each category
    foreach ($categories as &$category) {
        // Get subcategories for this category
        $subcategoriesStmt = $pdo->prepare("
            SELECT s.*, 
                   COUNT(p.id) as product_count
            FROM subcategories s 
            LEFT JOIN products p ON s.id = p.subcategory_id AND p.is_active = 1
            WHERE s.category_id = :category_id AND s.is_active = 1
            GROUP BY s.id
            ORDER BY s.created_at DESC
        ");
        $subcategoriesStmt->execute([':category_id' => $category['id']]);
        $subcategories = $subcategoriesStmt->fetchAll(PDO::FETCH_ASSOC);
        
        // Get products for each subcategory (limited to 5 per subcategory for performance)
        foreach ($subcategories as &$subcategory) {
            $productsStmt = $pdo->prepare("
                SELECT p.id, p.name, p.slug, p.price, p.type, p.brand, p.is_active, p.short_description, p.description, p.discount_price,
                       (SELECT image_url FROM product_images WHERE product_id = p.id AND is_primary = 1 LIMIT 1) AS primary_image_url
                FROM products p
                WHERE p.subcategory_id = :subcategory_id AND p.is_active = 1
                ORDER BY p.created_at DESC
                LIMIT 5
            ");
            $productsStmt->execute([':subcategory_id' => $subcategory['id']]);
            $products = $productsStmt->fetchAll(PDO::FETCH_ASSOC);
            
            // Get all images for each product
            foreach ($products as &$product) {
                $imagesStmt = $pdo->prepare("
                    SELECT id, image_url, is_primary, display_order
                    FROM product_images 
                    WHERE product_id = :product_id 
                    ORDER BY is_primary DESC, display_order ASC
                ");
                $imagesStmt->execute([':product_id' => $product['id']]);
                $product['images'] = $imagesStmt->fetchAll(PDO::FETCH_ASSOC);
                
                // Get all specifications for each product
                $specificationsStmt = $pdo->prepare("
                    SELECT id, spec_name, spec_value, spec_group, display_order
                    FROM product_specifications 
                    WHERE product_id = :product_id 
                    ORDER BY spec_group IS NULL, spec_group, display_order, spec_name
                ");
                $specificationsStmt->execute([':product_id' => $product['id']]);
                $product['specifications'] = $specificationsStmt->fetchAll(PDO::FETCH_ASSOC);
            }
            
            $subcategory['products'] = $products;
        }
        
        $category['subcategories'] = $subcategories;
    }
    
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
    error_log("Database error in categories/get_with_subcategories_and_products.php: " . $e->getMessage());
    http_response_code(500);
    echo json_encode(['error' => 'Database error occurred']);
} catch (Exception $e) {
    error_log("General error in categories/get_with_subcategories_and_products.php: " . $e->getMessage());
    http_response_code(500);
    echo json_encode(['error' => 'An error occurred']);
}
?>