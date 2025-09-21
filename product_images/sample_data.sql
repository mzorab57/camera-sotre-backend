-- Product Images Table Schema and Sample Data
-- This file contains the table structure and sample data for the product_images table

-- Create product_images table
CREATE TABLE IF NOT EXISTS product_images (
  id INT PRIMARY KEY AUTO_INCREMENT,
  product_id INT NOT NULL,
  image_url VARCHAR(500) NOT NULL,
  is_primary BOOLEAN DEFAULT FALSE,
  display_order INT DEFAULT 0,
  created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
  updated_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
  FOREIGN KEY (product_id) REFERENCES products(id) ON DELETE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- Add indexes for better performance
CREATE INDEX idx_product_images_product_id ON product_images(product_id);
CREATE INDEX idx_product_images_primary ON product_images(product_id, is_primary);
CREATE INDEX idx_product_images_order ON product_images(product_id, display_order);

-- Insert sample product images data
INSERT INTO product_images (
  product_id, image_url, is_primary, display_order
) VALUES

-- Canon EOS R5 Images (product_id: 1)
(1, '/uploads/products/20240115103000-canon-r5-front.jpg', TRUE, 0),
(1, '/uploads/products/20240115103010-canon-r5-back.jpg', FALSE, 10),
(1, '/uploads/products/20240115103020-canon-r5-side.jpg', FALSE, 20),
(1, '/uploads/products/20240115103030-canon-r5-top.jpg', FALSE, 30),
(1, 'https://example.com/canon-r5-accessories.jpg', FALSE, 40),

-- Sony A7 IV Images (product_id: 2)
(2, '/uploads/products/20240115104000-sony-a7iv-main.jpg', TRUE, 0),
(2, '/uploads/products/20240115104010-sony-a7iv-display.jpg', FALSE, 10),
(2, '/uploads/products/20240115104020-sony-a7iv-controls.jpg', FALSE, 20),
(2, 'https://example.com/sony-a7iv-sample-photos.jpg', FALSE, 30),

-- Nikon Z6 II Images (product_id: 3)
(3, '/uploads/products/20240115105000-nikon-z6ii-hero.jpg', TRUE, 0),
(3, '/uploads/products/20240115105010-nikon-z6ii-grip.jpg', FALSE, 10),
(3, 'https://example.com/nikon-z6ii-video-sample.jpg', FALSE, 20),

-- Canon RF 24-70mm f/2.8L Images (product_id: 4)
(4, '/uploads/products/20240115106000-canon-rf2470-main.jpg', TRUE, 0),
(4, '/uploads/products/20240115106010-canon-rf2470-mount.jpg', FALSE, 10),
(4, '/uploads/products/20240115106020-canon-rf2470-controls.jpg', FALSE, 20),
(4, '/uploads/products/20240115106030-canon-rf2470-size.jpg', FALSE, 30),

-- Sony FE 85mm f/1.4 GM Images (product_id: 5)
(5, '/uploads/products/20240115107000-sony-85gm-hero.jpg', TRUE, 0),
(5, '/uploads/products/20240115107010-sony-85gm-bokeh.jpg', FALSE, 10),
(5, 'https://example.com/sony-85gm-sample-portraits.jpg', FALSE, 20),

-- Sigma 24-70mm f/2.8 Art Images (product_id: 6)
(6, '/uploads/products/20240115108000-sigma-2470art-main.jpg', TRUE, 0),
(6, '/uploads/products/20240115108010-sigma-2470art-build.jpg', FALSE, 10),
(6, 'https://example.com/sigma-2470art-sharpness-test.jpg', FALSE, 20),

-- Manfrotto MT055CXPRO4 Images (product_id: 7)
(7, '/uploads/products/20240115109000-manfrotto-055cx-setup.jpg', TRUE, 0),
(7, '/uploads/products/20240115109010-manfrotto-055cx-legs.jpg', FALSE, 10),
(7, '/uploads/products/20240115109020-manfrotto-055cx-column.jpg', FALSE, 20),
(7, '/uploads/products/20240115109030-manfrotto-055cx-compact.jpg', FALSE, 30),

-- Gitzo GT3543XLS Images (product_id: 8)
(8, '/uploads/products/20240115110000-gitzo-3543xls-hero.jpg', TRUE, 0),
(8, '/uploads/products/20240115110010-gitzo-3543xls-carbon.jpg', FALSE, 10),
(8, 'https://example.com/gitzo-3543xls-mountain-use.jpg', FALSE, 20),

-- Godox AD600Pro Images (product_id: 9)
(9, '/uploads/products/20240115111000-godox-ad600pro-main.jpg', TRUE, 0),
(9, '/uploads/products/20240115111010-godox-ad600pro-controls.jpg', FALSE, 10),
(9, '/uploads/products/20240115111020-godox-ad600pro-modifiers.jpg', FALSE, 20),
(9, 'https://example.com/godox-ad600pro-setup-examples.jpg', FALSE, 30),

-- Profoto B10 Plus Images (product_id: 10)
(10, '/uploads/products/20240115112000-profoto-b10plus-hero.jpg', TRUE, 0),
(10, '/uploads/products/20240115112010-profoto-b10plus-app.jpg', FALSE, 10),
(10, 'https://example.com/profoto-b10plus-modeling-light.jpg', FALSE, 20),

-- DJI Ronin-S Images (product_id: 11)
(11, '/uploads/products/20240115113000-dji-ronins-gimbal.jpg', TRUE, 0),
(11, '/uploads/products/20240115113010-dji-ronins-camera-mount.jpg', FALSE, 10),
(11, '/uploads/products/20240115113020-dji-ronins-controls.jpg', FALSE, 20),
(11, 'https://example.com/dji-ronins-smooth-footage.jpg', FALSE, 30),

-- Zhiyun Crane 3S Images (product_id: 12)
(12, '/uploads/products/20240115114000-zhiyun-crane3s-main.jpg', TRUE, 0),
(12, '/uploads/products/20240115114010-zhiyun-crane3s-payload.jpg', FALSE, 10),
(12, 'https://example.com/zhiyun-crane3s-professional-use.jpg', FALSE, 20),

-- SanDisk CFexpress Images (product_id: 13)
(13, '/uploads/products/20240115115000-sandisk-cfexpress-card.jpg', TRUE, 0),
(13, '/uploads/products/20240115115010-sandisk-cfexpress-speed.jpg', FALSE, 10),
(13, 'https://example.com/sandisk-cfexpress-8k-recording.jpg', FALSE, 20),

-- Lexar SDXC Images (product_id: 14)
(14, '/uploads/products/20240115116000-lexar-2000x-card.jpg', TRUE, 0),
(14, '/uploads/products/20240115116010-lexar-2000x-performance.jpg', FALSE, 10),

-- Canon EOS R6 Mark II Images (product_id: 15 - inactive)
(15, '/uploads/products/20240115117000-canon-r6m2-main.jpg', TRUE, 0),
(15, '/uploads/products/20240115117010-canon-r6m2-features.jpg', FALSE, 10),

-- Tamron 28-75mm G2 Images (product_id: 16 - inactive)
(16, '/uploads/products/20240115118000-tamron-2875g2-lens.jpg', TRUE, 0),
(16, 'https://example.com/tamron-2875g2-improvements.jpg', FALSE, 10);

-- Display inserted data with product information
SELECT 
  pi.id,
  pi.product_id,
  p.name AS product_name,
  p.model AS product_model,
  pi.image_url,
  pi.is_primary,
  pi.display_order,
  pi.created_at
FROM product_images pi
LEFT JOIN products p ON pi.product_id = p.id
ORDER BY pi.product_id, pi.is_primary DESC, pi.display_order ASC;

-- Show image count by product
SELECT 
  p.id AS product_id,
  p.name AS product_name,
  p.model AS product_model,
  COUNT(pi.id) AS total_images,
  COUNT(CASE WHEN pi.is_primary = 1 THEN 1 END) AS primary_images,
  GROUP_CONCAT(
    CASE WHEN pi.is_primary = 1 
    THEN CONCAT('PRIMARY: ', pi.image_url) 
    END
  ) AS primary_image_url
FROM products p
LEFT JOIN product_images pi ON p.id = pi.product_id
GROUP BY p.id, p.name, p.model
ORDER BY p.id;

-- Show products without images (should be empty with this sample data)
SELECT 
  p.id,
  p.name,
  p.model
FROM products p
LEFT JOIN product_images pi ON p.id = pi.product_id
WHERE pi.id IS NULL;

-- Show image source distribution (local vs external)
SELECT 
  CASE 
    WHEN image_url LIKE '/uploads/%' THEN 'Local Upload'
    WHEN image_url LIKE 'http%' THEN 'External URL'
    ELSE 'Other'
  END AS image_source,
  COUNT(*) AS image_count,
  ROUND(COUNT(*) * 100.0 / (SELECT COUNT(*) FROM product_images), 2) AS percentage
FROM product_images
GROUP BY 
  CASE 
    WHEN image_url LIKE '/uploads/%' THEN 'Local Upload'
    WHEN image_url LIKE 'http%' THEN 'External URL'
    ELSE 'Other'
  END
ORDER BY image_count DESC;

-- Show display order distribution
SELECT 
  display_order,
  COUNT(*) AS image_count
FROM product_images
GROUP BY display_order
ORDER BY display_order;

-- Verify primary image constraints (each product should have exactly one primary)
SELECT 
  product_id,
  COUNT(CASE WHEN is_primary = 1 THEN 1 END) AS primary_count,
  CASE 
    WHEN COUNT(CASE WHEN is_primary = 1 THEN 1 END) = 0 THEN 'NO PRIMARY'
    WHEN COUNT(CASE WHEN is_primary = 1 THEN 1 END) = 1 THEN 'CORRECT'
    ELSE 'MULTIPLE PRIMARY'
  END AS status
FROM product_images
GROUP BY product_id
ORDER BY product_id;