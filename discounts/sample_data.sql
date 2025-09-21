-- Create discounts table
CREATE TABLE IF NOT EXISTS discounts (
    id INT PRIMARY KEY AUTO_INCREMENT,
    name VARCHAR(255) NOT NULL,
    description TEXT,
    discount_type ENUM('percentage', 'fixed_amount') NOT NULL DEFAULT 'percentage',
    discount_value DECIMAL(10,2) NOT NULL,
    
    -- Target specification
    target_type ENUM('product', 'category', 'subcategory') NOT NULL,
    target_id INT NOT NULL,
    
    -- Date range
    start_date DATETIME NOT NULL,
    end_date DATETIME NULL,
    
    -- Status and priority
    is_active BOOLEAN DEFAULT TRUE,
    priority INT DEFAULT 0, -- Higher number = higher priority
    
    -- Limits
    max_uses INT NULL, -- Maximum number of uses
    used_count INT DEFAULT 0,
    min_order_amount DECIMAL(10,2) NULL,
    
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    updated_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
    
    INDEX idx_target (target_type, target_id),
    INDEX idx_active_dates (is_active, start_date, end_date),
    INDEX idx_priority (priority DESC)
);

-- Insert sample data
INSERT INTO discounts (
    name, description, discount_type, discount_value, target_type, target_id,
    start_date, end_date, is_active, priority, max_uses, min_order_amount
) VALUES 
(
    'Summer Sale 2024',
    '20% off on all electronics',
    'percentage',
    20.00,
    'category',
    1,
    '2024-06-01 00:00:00',
    '2024-08-31 23:59:59',
    TRUE,
    10,
    1000,
    50.00
),
(
    'New Customer Discount',
    '$10 off for first-time buyers',
    'fixed_amount',
    10.00,
    'product',
    5,
    '2024-01-01 00:00:00',
    NULL,
    TRUE,
    5,
    NULL,
    25.00
),
(
    'Flash Sale',
    '50% off selected items',
    'percentage',
    50.00,
    'subcategory',
    3,
    '2024-12-01 00:00:00',
    '2024-12-07 23:59:59',
    TRUE,
    15,
    100,
    NULL
),
(
    'Clearance Sale',
    '$25 off clearance items',
    'fixed_amount',
    25.00,
    'category',
    2,
    '2024-11-01 00:00:00',
    '2024-11-30 23:59:59',
    FALSE,
    1,
    500,
    100.00
);

-- Query examples for testing

-- Get all active discounts
-- SELECT * FROM discounts WHERE is_active = TRUE AND start_date <= NOW() AND (end_date IS NULL OR end_date >= NOW());

-- Get discounts by target type
-- SELECT * FROM discounts WHERE target_type = 'category' AND target_id = 1;

-- Get discounts ordered by priority
-- SELECT * FROM discounts WHERE is_active = TRUE ORDER BY priority DESC, created_at DESC;

-- Update discount usage count
-- UPDATE discounts SET used_count = used_count + 1 WHERE id = 1;

-- Check if discount is still valid and has uses left
-- SELECT * FROM discounts WHERE id = 1 AND is_active = TRUE AND start_date <= NOW() AND (end_date IS NULL OR end_date >= NOW()) AND (max_uses IS NULL OR used_count < max_uses);