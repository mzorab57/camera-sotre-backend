-- Products Table Schema and Sample Data
-- This file contains the table structure and sample data for the products table

-- Create products table
CREATE TABLE IF NOT EXISTS products (
  id INT PRIMARY KEY AUTO_INCREMENT,
  subcategory_id INT NOT NULL,
  category_id INT, -- Optional direct category reference
  name VARCHAR(255) NOT NULL,
  model VARCHAR(255),
  slug VARCHAR(255) UNIQUE NOT NULL,
  sku VARCHAR(100),
  description TEXT,
  short_description TEXT,
  price DECIMAL(10,2) NOT NULL,
  discount_price DECIMAL(10,2),
  type ENUM('videography', 'photography', 'both') NOT NULL,
  brand VARCHAR(100),
  is_featured BOOLEAN DEFAULT FALSE,
  is_active BOOLEAN DEFAULT TRUE,
  meta_title VARCHAR(255),
  meta_description TEXT,
  created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
  updated_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
  FOREIGN KEY (subcategory_id) REFERENCES subcategories(id) ON DELETE CASCADE,
  FOREIGN KEY (category_id) REFERENCES categories(id) ON DELETE SET NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- Add fulltext index for search functionality
ALTER TABLE products ADD FULLTEXT(name, model, sku, description);

-- Insert sample products data
INSERT INTO products (
  subcategory_id, category_id, name, model, slug, sku, description, short_description, 
  price, discount_price, type, brand, is_featured, is_active, meta_title, meta_description
) VALUES

-- Camera Products
(1, 1, 'Canon EOS R5', 'EOS R5', 'canon-eos-r5', 'CAN-R5-001', 
 'Professional full-frame mirrorless camera with 45MP sensor, 8K video recording, and advanced autofocus system. Perfect for both photography and videography professionals.', 
 '45MP full-frame mirrorless camera with 8K video', 
 3899.00, 3599.00, 'both', 'Canon', TRUE, TRUE, 
 'Canon EOS R5 - Professional Mirrorless Camera', 
 'High-resolution full-frame mirrorless camera for professional photography and videography'),

(1, 1, 'Sony A7 IV', 'A7 IV', 'sony-a7-iv', 'SON-A7IV-001', 
 'Versatile full-frame mirrorless camera with 33MP sensor, 4K video, and excellent low-light performance. Ideal for content creators and professionals.', 
 '33MP full-frame camera with 4K video', 
 2498.00, NULL, 'both', 'Sony', TRUE, TRUE, 
 'Sony A7 IV - Full Frame Mirrorless Camera', 
 'Professional full-frame camera with advanced features for photography and video'),

(1, 1, 'Nikon Z6 II', 'Z6 II', 'nikon-z6-ii', 'NIK-Z6II-001', 
 'Full-frame mirrorless camera with dual processors, excellent image quality, and robust build. Great for wedding and portrait photography.', 
 '24.5MP full-frame with dual processors', 
 1996.95, 1796.95, 'photography', 'Nikon', FALSE, TRUE, 
 'Nikon Z6 II - Professional Photography Camera', 
 'Advanced full-frame mirrorless camera for professional photographers'),

-- Lens Products  
(2, 1, 'Canon RF 24-70mm f/2.8L IS USM', 'RF 24-70mm f/2.8L', 'canon-rf-24-70mm-f28l', 'CAN-RF2470-001', 
 'Professional standard zoom lens with constant f/2.8 aperture, image stabilization, and weather sealing. Essential lens for Canon RF system.', 
 'Professional 24-70mm f/2.8 zoom lens', 
 2299.00, NULL, 'both', 'Canon', TRUE, TRUE, 
 'Canon RF 24-70mm f/2.8L IS USM Lens', 
 'Professional standard zoom lens for Canon RF mirrorless cameras'),

(2, 1, 'Sony FE 85mm f/1.4 GM', 'FE 85mm f/1.4 GM', 'sony-fe-85mm-f14-gm', 'SON-85GM-001', 
 'Premium portrait lens with exceptional bokeh, sharp optics, and fast autofocus. Perfect for portrait and wedding photography.', 
 'Premium 85mm portrait lens with f/1.4 aperture', 
 1798.00, 1598.00, 'photography', 'Sony', TRUE, TRUE, 
 'Sony FE 85mm f/1.4 GM Portrait Lens', 
 'Professional portrait lens with beautiful bokeh and sharp optics'),

(2, 1, 'Sigma 24-70mm f/2.8 DG DN Art', '24-70mm f/2.8 DG DN Art', 'sigma-24-70mm-f28-art', 'SIG-2470ART-001', 
 'High-performance standard zoom lens with excellent optical quality and build. Compatible with Sony E and L-mount systems.', 
 'Professional 24-70mm f/2.8 Art lens', 
 1099.00, 999.00, 'both', 'Sigma', FALSE, TRUE, 
 'Sigma 24-70mm f/2.8 DG DN Art Lens', 
 'Professional standard zoom lens with exceptional optical performance'),

-- Tripod Products
(3, 2, 'Manfrotto MT055CXPRO4', 'MT055CXPRO4', 'manfrotto-mt055cxpro4', 'MAN-055CX-001', 
 'Professional carbon fiber tripod with 4-section legs, horizontal column, and excellent stability. Perfect for landscape and studio photography.', 
 'Carbon fiber tripod with horizontal column', 
 449.99, 399.99, 'photography', 'Manfrotto', TRUE, TRUE, 
 'Manfrotto MT055CXPRO4 Carbon Fiber Tripod', 
 'Professional carbon fiber tripod for photography and videography'),

(3, 2, 'Gitzo GT3543XLS Mountaineer', 'GT3543XLS', 'gitzo-gt3543xls-mountaineer', 'GIT-3543XLS-001', 
 'Ultra-lightweight carbon fiber tripod designed for travel and outdoor photography. Exceptional stability and portability.', 
 'Lightweight carbon fiber travel tripod', 
 899.00, NULL, 'photography', 'Gitzo', TRUE, TRUE, 
 'Gitzo GT3543XLS Mountaineer Carbon Tripod', 
 'Premium lightweight carbon fiber tripod for travel photography'),

-- Lighting Products
(4, 3, 'Godox AD600Pro', 'AD600Pro', 'godox-ad600pro', 'GOD-AD600P-001', 
 'Powerful portable flash with 600Ws output, TTL support, and wireless control. Ideal for portrait and commercial photography.', 
 '600Ws portable flash with TTL and wireless', 
 899.00, 799.00, 'photography', 'Godox', TRUE, TRUE, 
 'Godox AD600Pro Portable Flash', 
 'Professional portable flash system for studio and location photography'),

(4, 3, 'Profoto B10 Plus', 'B10 Plus', 'profoto-b10-plus', 'PRO-B10P-001', 
 'Compact and powerful off-camera flash with smartphone app control, modeling light, and exceptional color accuracy.', 
 'Compact 500Ws flash with app control', 
 1995.00, NULL, 'photography', 'Profoto', TRUE, TRUE, 
 'Profoto B10 Plus Off-Camera Flash', 
 'Premium compact flash system with smartphone control and modeling light'),

-- Video Equipment
(5, 4, 'DJI Ronin-S', 'Ronin-S', 'dji-ronin-s', 'DJI-RONINS-001', 
 'Professional 3-axis gimbal stabilizer for DSLR and mirrorless cameras. Smooth footage for filmmaking and content creation.', 
 '3-axis gimbal for DSLR and mirrorless cameras', 
 699.00, 599.00, 'videography', 'DJI', TRUE, TRUE, 
 'DJI Ronin-S 3-Axis Gimbal Stabilizer', 
 'Professional gimbal stabilizer for smooth video recording'),

(5, 4, 'Zhiyun Crane 3S', 'Crane 3S', 'zhiyun-crane-3s', 'ZHI-C3S-001', 
 'Heavy-duty 3-axis gimbal with 6.5kg payload capacity, modular design, and advanced stabilization algorithms.', 
 'Heavy-duty gimbal with 6.5kg payload', 
 899.00, NULL, 'videography', 'Zhiyun', FALSE, TRUE, 
 'Zhiyun Crane 3S Professional Gimbal', 
 'Heavy-duty 3-axis gimbal for professional video production'),

-- Memory Cards
(6, 5, 'SanDisk Extreme Pro CFexpress', 'Extreme Pro CFexpress Type B', 'sandisk-extreme-pro-cfexpress-128gb', 'SAN-CFXP128-001', 
 'High-speed CFexpress Type B memory card with 128GB capacity, perfect for 8K video recording and high-resolution photography.', 
 '128GB CFexpress Type B card for 8K video', 
 199.99, 179.99, 'both', 'SanDisk', FALSE, TRUE, 
 'SanDisk Extreme Pro CFexpress 128GB', 
 'High-speed memory card for professional cameras and 8K video recording'),

(6, 5, 'Lexar Professional 2000x SDXC', '2000x SDXC UHS-II', 'lexar-2000x-sdxc-64gb', 'LEX-2000X64-001', 
 'Professional SDXC card with UHS-II technology, 64GB capacity, and fast read/write speeds for 4K video and burst photography.', 
 '64GB SDXC UHS-II card for 4K video', 
 89.99, NULL, 'both', 'Lexar', FALSE, TRUE, 
 'Lexar Professional 2000x SDXC 64GB', 
 'Professional memory card with fast speeds for 4K video and photography'),

-- Inactive Products (for testing)
(1, 1, 'Canon EOS R6 Mark II', 'EOS R6 Mark II', 'canon-eos-r6-mark-ii', 'CAN-R6M2-001', 
 'Advanced full-frame mirrorless camera with improved autofocus and video capabilities.', 
 '24.2MP full-frame with advanced AF', 
 2499.00, NULL, 'both', 'Canon', FALSE, FALSE, 
 'Canon EOS R6 Mark II Camera', 
 'Advanced full-frame mirrorless camera for enthusiasts and professionals'),

(2, 1, 'Tamron 28-75mm f/2.8 Di III RXD G2', '28-75mm f/2.8 G2', 'tamron-28-75mm-f28-g2', 'TAM-2875G2-001', 
 'Updated version of the popular standard zoom lens with improved optics and faster autofocus.', 
 'Updated 28-75mm f/2.8 zoom lens', 
 899.00, 799.00, 'both', 'Tamron', FALSE, FALSE, 
 'Tamron 28-75mm f/2.8 Di III RXD G2', 
 'Professional standard zoom lens with improved performance');

-- Display inserted data with category and subcategory names
SELECT 
  p.id,
  p.name,
  p.model,
  p.slug,
  p.sku,
  p.price,
  p.discount_price,
  p.type,
  p.brand,
  p.is_featured,
  p.is_active,
  s.name AS subcategory_name,
  c.name AS category_name,
  p.created_at
FROM products p
LEFT JOIN subcategories s ON p.subcategory_id = s.id
LEFT JOIN categories c ON s.category_id = c.id
ORDER BY p.created_at DESC;

-- Show products count by category and subcategory
SELECT 
  c.name AS category_name,
  s.name AS subcategory_name,
  COUNT(p.id) AS product_count,
  COUNT(CASE WHEN p.is_active = 1 THEN 1 END) AS active_products,
  COUNT(CASE WHEN p.is_featured = 1 THEN 1 END) AS featured_products
FROM categories c
LEFT JOIN subcategories s ON c.id = s.category_id
LEFT JOIN products p ON s.id = p.subcategory_id
GROUP BY c.id, s.id
ORDER BY c.name, s.name;

-- Show products by type
SELECT 
  type,
  COUNT(*) AS total_products,
  COUNT(CASE WHEN is_active = 1 THEN 1 END) AS active_products,
  AVG(price) AS average_price,
  MIN(price) AS min_price,
  MAX(price) AS max_price
FROM products
GROUP BY type
ORDER BY type;

-- Show products by brand
SELECT 
  brand,
  COUNT(*) AS product_count,
  COUNT(CASE WHEN is_active = 1 THEN 1 END) AS active_products,
  AVG(price) AS average_price
FROM products
WHERE brand IS NOT NULL AND brand != ''
GROUP BY brand
ORDER BY product_count DESC;