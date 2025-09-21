<?php
require_once __DIR__ . '/../config/db.php';

function calculateProductDiscount($productId, $subcategoryId = null, $categoryId = null) {
    $pdo = db();
    
    $now = date('Y-m-d H:i:s');
    
    // Get all applicable discounts ordered by priority
    $sql = "SELECT * FROM discounts 
            WHERE is_active = 1 
            AND start_date <= :now 
            AND (end_date IS NULL OR end_date >= :now)
            AND (
                (target_type = 'product' AND target_id = :product_id) OR
                (target_type = 'subcategory' AND target_id = :subcategory_id) OR
                (target_type = 'category' AND target_id = :category_id)
            )
            ORDER BY 
                CASE target_type 
                    WHEN 'product' THEN 3
                    WHEN 'subcategory' THEN 2  
                    WHEN 'category' THEN 1
                END DESC,
                priority DESC,
                discount_value DESC";
    
    $stmt = $pdo->prepare($sql);
    $stmt->execute([
        ':now' => $now,
        ':product_id' => $productId,
        ':subcategory_id' => $subcategoryId,
        ':category_id' => $categoryId
    ]);
    
    $discount = $stmt->fetch();
    
    return $discount ?: null;
}

function applyDiscount($originalPrice, $discount) {
    if (!$discount) return $originalPrice;
    
    if ($discount['discount_type'] === 'percentage') {
        $discountAmount = ($originalPrice * $discount['discount_value']) / 100;
        return max(0, $originalPrice - $discountAmount);
    } else {
        return max(0, $originalPrice - $discount['discount_value']);
    }
}
?>