-- Product Tags API Sample Data
-- This file contains the table schema and sample data for the product_tags junction table
-- Run this file after creating the products and tags tables

-- Database Configuration
SET NAMES utf8mb4;
SET FOREIGN_KEY_CHECKS = 0;

-- =====================================================
-- TABLE SCHEMA
-- =====================================================

-- Product Tags Junction Table
CREATE TABLE IF NOT EXISTS product_tags (
  product_id INT NOT NULL,
  tag_id INT NOT NULL,
  PRIMARY KEY (product_id, tag_id),
  FOREIGN KEY (product_id) REFERENCES products(id) ON DELETE CASCADE,
  FOREIGN KEY (tag_id) REFERENCES tags(id) ON DELETE CASCADE,
  INDEX idx_product_tags_tag (tag_id)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- =====================================================
-- SAMPLE DATA
-- =====================================================

-- Clear existing data
DELETE FROM product_tags;

-- Sample Product-Tag Associations
-- Assuming we have products with IDs 1-10 and tags with IDs 1-30

-- Product 1: Canon EOS R5 (Professional Full-Frame Camera)
INSERT INTO product_tags (product_id, tag_id) VALUES
(1, 1),  -- Professional Camera
(1, 2),  -- Full Frame
(1, 3),  -- Mirrorless
(1, 4),  -- 4K Video
(1, 5),  -- Weather Sealed
(1, 6),  -- High Resolution
(1, 7),  -- Canon Mount
(1, 8);  -- Portrait Photography

-- Product 2: Sony A7 IV (Professional Full-Frame Camera)
INSERT INTO product_tags (product_id, tag_id) VALUES
(2, 1),  -- Professional Camera
(2, 2),  -- Full Frame
(2, 3),  -- Mirrorless
(2, 4),  -- 4K Video
(2, 5),  -- Weather Sealed
(2, 6),  -- High Resolution
(2, 9),  -- Sony Mount
(2, 10), -- Landscape Photography
(2, 11); -- Low Light

-- Product 3: Nikon D850 (Professional DSLR)
INSERT INTO product_tags (product_id, tag_id) VALUES
(3, 1),  -- Professional Camera
(3, 2),  -- Full Frame
(3, 12), -- DSLR
(3, 6),  -- High Resolution
(3, 5),  -- Weather Sealed
(3, 13), -- Nikon Mount
(3, 8),  -- Portrait Photography
(3, 14); -- Studio Photography

-- Product 4: Canon EOS R6 Mark II (Enthusiast Camera)
INSERT INTO product_tags (product_id, tag_id) VALUES
(4, 15), -- Enthusiast Camera
(4, 2),  -- Full Frame
(4, 3),  -- Mirrorless
(4, 4),  -- 4K Video
(4, 11), -- Low Light
(4, 7),  -- Canon Mount
(4, 16), -- Sports Photography
(4, 17); -- Wildlife Photography

-- Product 5: Sony A6700 (APS-C Mirrorless)
INSERT INTO product_tags (product_id, tag_id) VALUES
(5, 15), -- Enthusiast Camera
(5, 18), -- APS-C
(5, 3),  -- Mirrorless
(5, 4),  -- 4K Video
(5, 9),  -- Sony Mount
(5, 19), -- Travel Photography
(5, 20), -- Street Photography
(5, 21); -- Compact

-- Product 6: Canon RF 24-70mm f/2.8L (Professional Zoom Lens)
INSERT INTO product_tags (product_id, tag_id) VALUES
(6, 22), -- Zoom Lens
(6, 23), -- Professional Lens
(6, 7),  -- Canon Mount
(6, 24), -- Standard Zoom
(6, 5),  -- Weather Sealed
(6, 8),  -- Portrait Photography
(6, 10), -- Landscape Photography
(6, 25); -- Fast Aperture

-- Product 7: Sony FE 85mm f/1.4 GM (Portrait Prime Lens)
INSERT INTO product_tags (product_id, tag_id) VALUES
(7, 26), -- Prime Lens
(7, 23), -- Professional Lens
(7, 9),  -- Sony Mount
(7, 8),  -- Portrait Photography
(7, 25), -- Fast Aperture
(7, 27), -- Bokeh
(7, 14); -- Studio Photography

-- Product 8: Nikon AF-S 70-200mm f/2.8E FL ED VR (Telephoto Zoom)
INSERT INTO product_tags (product_id, tag_id) VALUES
(8, 22), -- Zoom Lens
(8, 23), -- Professional Lens
(8, 13), -- Nikon Mount
(8, 28), -- Telephoto
(8, 16), -- Sports Photography
(8, 17), -- Wildlife Photography
(8, 25), -- Fast Aperture
(8, 5);  -- Weather Sealed

-- Product 9: Canon EOS M50 Mark II (Entry-Level Mirrorless)
INSERT INTO product_tags (product_id, tag_id) VALUES
(9, 29), -- Entry Level
(9, 18), -- APS-C
(9, 3),  -- Mirrorless
(9, 4),  -- 4K Video
(9, 30), -- Canon EF-M Mount
(9, 19), -- Travel Photography
(9, 20), -- Street Photography
(9, 21); -- Compact

-- Product 10: Sony FE 16-35mm f/2.8 GM (Wide-Angle Zoom)
INSERT INTO product_tags (product_id, tag_id) VALUES
(10, 22), -- Zoom Lens
(10, 23), -- Professional Lens
(10, 9),  -- Sony Mount
(10, 31), -- Wide Angle
(10, 10), -- Landscape Photography
(10, 32), -- Architecture Photography
(10, 25), -- Fast Aperture
(10, 5);  -- Weather Sealed

-- Additional associations for demonstration
-- Some products with multiple photography styles
INSERT INTO product_tags (product_id, tag_id) VALUES
-- Canon EOS R5 additional tags
(1, 16), -- Sports Photography
(1, 17), -- Wildlife Photography
(1, 14), -- Studio Photography

-- Sony A7 IV additional tags
(2, 16), -- Sports Photography
(2, 32), -- Architecture Photography

-- Nikon D850 additional tags
(3, 10), -- Landscape Photography
(3, 17), -- Wildlife Photography

-- Canon RF 24-70mm additional tags
(6, 16), -- Sports Photography
(6, 19), -- Travel Photography

-- Sony FE 85mm additional tags
(7, 33), -- Wedding Photography
(7, 34); -- Fashion Photography

-- =====================================================
-- VERIFICATION QUERIES
-- =====================================================

-- Display all product-tag relationships
SELECT 
    pt.product_id,
    pt.tag_id,
    p.name AS product_name,
    p.price,
    t.name AS tag_name,
    t.slug AS tag_slug
FROM product_tags pt
JOIN products p ON pt.product_id = p.id
JOIN tags t ON pt.tag_id = t.id
ORDER BY pt.product_id, t.name;

-- Count tags per product
SELECT 
    p.id,
    p.name AS product_name,
    COUNT(pt.tag_id) AS tag_count
FROM products p
LEFT JOIN product_tags pt ON p.id = pt.product_id
GROUP BY p.id, p.name
ORDER BY tag_count DESC;

-- Count products per tag
SELECT 
    t.id,
    t.name AS tag_name,
    t.slug,
    COUNT(pt.product_id) AS product_count
FROM tags t
LEFT JOIN product_tags pt ON t.id = pt.tag_id
GROUP BY t.id, t.name, t.slug
ORDER BY product_count DESC;

-- Find products with specific tag combinations
-- Products that are both "Professional Camera" and "Full Frame"
SELECT DISTINCT
    p.id,
    p.name,
    p.price
FROM products p
JOIN product_tags pt1 ON p.id = pt1.product_id
JOIN tags t1 ON pt1.tag_id = t1.id AND t1.slug = 'professional-camera'
JOIN product_tags pt2 ON p.id = pt2.product_id
JOIN tags t2 ON pt2.tag_id = t2.id AND t2.slug = 'full-frame'
ORDER BY p.name;

-- Find products suitable for portrait photography
SELECT DISTINCT
    p.id,
    p.name,
    p.price,
    GROUP_CONCAT(t.name ORDER BY t.name SEPARATOR ', ') AS all_tags
FROM products p
JOIN product_tags pt ON p.id = pt.product_id
JOIN tags t ON pt.tag_id = t.id
WHERE p.id IN (
    SELECT DISTINCT pt2.product_id
    FROM product_tags pt2
    JOIN tags t2 ON pt2.tag_id = t2.id
    WHERE t2.slug = 'portrait-photography'
)
GROUP BY p.id, p.name, p.price
ORDER BY p.name;

-- Most popular tags (by product count)
SELECT 
    t.name,
    t.slug,
    COUNT(pt.product_id) as usage_count,
    ROUND(COUNT(pt.product_id) * 100.0 / (
        SELECT COUNT(DISTINCT product_id) FROM product_tags
    ), 2) as usage_percentage
FROM tags t
JOIN product_tags pt ON t.id = pt.tag_id
GROUP BY t.id, t.name, t.slug
ORDER BY usage_count DESC
LIMIT 10;

-- Products with the most tags
SELECT 
    p.name,
    p.price,
    COUNT(pt.tag_id) as tag_count,
    GROUP_CONCAT(t.name ORDER BY t.name SEPARATOR ', ') as tags
FROM products p
JOIN product_tags pt ON p.id = pt.product_id
JOIN tags t ON pt.tag_id = t.id
GROUP BY p.id, p.name, p.price
ORDER BY tag_count DESC
LIMIT 5;

-- Photography style analysis
SELECT 
    CASE 
        WHEN t.name LIKE '%Photography' THEN t.name
        ELSE 'Other'
    END as category,
    COUNT(DISTINCT pt.product_id) as product_count
FROM tags t
JOIN product_tags pt ON t.id = pt.tag_id
GROUP BY category
ORDER BY product_count DESC;

-- Camera type distribution
SELECT 
    t.name as camera_type,
    COUNT(DISTINCT pt.product_id) as product_count
FROM tags t
JOIN product_tags pt ON t.id = pt.tag_id
WHERE t.name IN ('Professional Camera', 'Enthusiast Camera', 'Entry Level')
GROUP BY t.name
ORDER BY product_count DESC;

-- Lens mount compatibility
SELECT 
    t.name as mount_type,
    COUNT(DISTINCT pt.product_id) as product_count
FROM tags t
JOIN product_tags pt ON t.id = pt.tag_id
WHERE t.name LIKE '%Mount'
GROUP BY t.name
ORDER BY product_count DESC;

-- =====================================================
-- SAMPLE API USAGE SCENARIOS
-- =====================================================

-- Scenario 1: Find all professional cameras
-- API Call: GET /get.php?tag_slug=professional-camera
-- Expected: Products 1, 2, 3

-- Scenario 2: Find all tags for Canon EOS R5
-- API Call: GET /get.php?product_id=1
-- Expected: 11 tags including Professional Camera, Full Frame, etc.

-- Scenario 3: Find all Sony mount products
-- API Call: GET /get.php?tag_slug=sony-mount
-- Expected: Products 2, 5, 7, 10

-- Scenario 4: Find products suitable for portrait photography
-- API Call: GET /get.php?tag_slug=portrait-photography
-- Expected: Products 1, 3, 6, 7

-- Scenario 5: Find all mirrorless cameras
-- API Call: GET /get.php?tag_slug=mirrorless
-- Expected: Products 1, 2, 4, 5, 9

-- =====================================================
-- NOTES
-- =====================================================

/*
Sample Data Summary:
- 10 products with comprehensive tag associations
- 34+ unique tags covering various aspects:
  * Camera types (Professional, Enthusiast, Entry Level)
  * Sensor formats (Full Frame, APS-C)
  * Camera systems (Mirrorless, DSLR)
  * Lens types (Prime, Zoom, Wide Angle, Telephoto)
  * Photography styles (Portrait, Landscape, Sports, etc.)
  * Technical features (4K Video, Weather Sealed, etc.)
  * Mount systems (Canon, Sony, Nikon)

Relationship Distribution:
- Each product has 7-11 tags on average
- Tags are distributed across different categories
- Realistic associations based on actual product features
- Demonstrates many-to-many relationships effectively

Use Cases Covered:
- Product discovery by tags
- Tag-based filtering and search
- Cross-selling based on shared tags
- Analytics and reporting
- Inventory categorization
- Marketing segmentation
*/

-- Re-enable foreign key checks
SET FOREIGN_KEY_CHECKS = 1;