-- Product Specifications Sample Data
-- This file contains the table schema and sample data for the product_specifications API

-- Optional: Create database and set encoding
CREATE DATABASE IF NOT EXISTS photography_store 
  CHARACTER SET utf8mb4 
  COLLATE utf8mb4_unicode_ci;
USE photography_store;

-- Product Specifications Table Schema
CREATE TABLE IF NOT EXISTS product_specifications (
  id INT PRIMARY KEY AUTO_INCREMENT,
  product_id INT NOT NULL,
  spec_name VARCHAR(255) NOT NULL,
  spec_value TEXT NOT NULL,
  spec_group VARCHAR(100),
  display_order INT DEFAULT 0,
  created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
  updated_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
  
  -- Indexes for better performance
  INDEX idx_product_id (product_id),
  INDEX idx_spec_group (spec_group),
  INDEX idx_display_order (display_order),
  INDEX idx_product_group_order (product_id, spec_group, display_order),
  
  -- Foreign key constraint (assumes products table exists)
  FOREIGN KEY (product_id) REFERENCES products(id) ON DELETE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- Sample Data for Product Specifications
-- Note: This assumes products with IDs 1-10 exist in the products table

-- Product 1: Professional DSLR Camera Specifications
INSERT INTO product_specifications (product_id, spec_name, spec_value, spec_group, display_order) VALUES
-- Camera Body Group
(1, 'Sensor Type', 'Full-frame CMOS', 'Camera Body', 0),
(1, 'Sensor Size', '35.9 x 24.0mm', 'Camera Body', 10),
(1, 'Resolution', '45.7 Megapixels', 'Camera Body', 20),
(1, 'Image Processor', 'EXPEED 6', 'Camera Body', 30),
(1, 'Mount Type', 'Nikon F-mount', 'Camera Body', 40),
(1, 'Viewfinder', 'Optical pentaprism, 100% coverage', 'Camera Body', 50),

-- Performance Group
(1, 'ISO Range', '64-25600 (expandable to 32-102400)', 'Performance', 0),
(1, 'Autofocus Points', '153-point AF system', 'Performance', 10),
(1, 'Continuous Shooting', '7 fps (9 fps in DX mode)', 'Performance', 20),
(1, 'Shutter Speed', '1/8000 to 30 sec', 'Performance', 30),
(1, 'Metering', '180K-pixel RGB sensor', 'Performance', 40),

-- Video Group
(1, 'Video Resolution', '4K UHD at 30fps', 'Video', 0),
(1, 'Video Format', 'MOV, MP4', 'Video', 10),
(1, 'Video Codec', 'H.264/MPEG-4 AVC', 'Video', 20),
(1, 'Slow Motion', '1080p at 120fps', 'Video', 30),

-- Connectivity Group
(1, 'Memory Cards', 'Dual CF/CFexpress + SD slots', 'Connectivity', 0),
(1, 'USB', 'USB 3.0 Type-C', 'Connectivity', 10),
(1, 'HDMI', 'Type A (full-size)', 'Connectivity', 20),
(1, 'WiFi', '802.11b/g/n/ac', 'Connectivity', 30),
(1, 'Bluetooth', 'Bluetooth 4.2', 'Connectivity', 40),

-- Physical Group
(1, 'Dimensions', '146 x 124 x 78.5mm', 'Physical', 0),
(1, 'Weight', '1005g (body only)', 'Physical', 10),
(1, 'Weather Sealing', 'Magnesium alloy, weather-sealed', 'Physical', 20),
(1, 'Battery', 'EN-EL15c Li-ion', 'Physical', 30),
(1, 'Battery Life', '1840 shots (CIPA standard)', 'Physical', 40);

-- Product 2: Professional Lens Specifications
INSERT INTO product_specifications (product_id, spec_name, spec_value, spec_group, display_order) VALUES
-- Optics Group
(2, 'Focal Length', '24-70mm', 'Optics', 0),
(2, 'Maximum Aperture', 'f/2.8 (constant)', 'Optics', 10),
(2, 'Minimum Aperture', 'f/22', 'Optics', 20),
(2, 'Lens Elements', '20 elements in 16 groups', 'Optics', 30),
(2, 'Special Elements', '2 ED, 3 aspherical, Nano Crystal Coat', 'Optics', 40),
(2, 'Minimum Focus', '0.38m (0.3x magnification)', 'Optics', 50),

-- Features Group
(2, 'Image Stabilization', 'Vibration Reduction (VR)', 'Features', 0),
(2, 'Autofocus Motor', 'Silent Wave Motor (SWM)', 'Features', 10),
(2, 'Focus Override', 'Manual focus override', 'Features', 20),
(2, 'Zoom Lock', 'Zoom lock at 24mm', 'Features', 30),

-- Build Group
(2, 'Filter Size', '82mm', 'Build', 0),
(2, 'Weather Sealing', 'Dust and moisture resistant', 'Build', 10),
(2, 'Dimensions', '88 x 154.5mm', 'Build', 20),
(2, 'Weight', '1070g', 'Build', 30),

-- Compatibility Group
(2, 'Mount', 'Nikon F-mount', 'Compatibility', 0),
(2, 'Format Coverage', 'Full-frame and DX', 'Compatibility', 10),
(2, 'Compatible Cameras', 'All Nikon F-mount DSLRs', 'Compatibility', 20);

-- Product 3: Tripod Specifications
INSERT INTO product_specifications (product_id, spec_name, spec_value, spec_group, display_order) VALUES
-- Specifications Group
(3, 'Material', 'Carbon fiber legs, aluminum head', 'Specifications', 0),
(3, 'Leg Sections', '4-section legs', 'Specifications', 10),
(3, 'Maximum Height', '1650mm (with center column extended)', 'Specifications', 20),
(3, 'Minimum Height', '185mm', 'Specifications', 30),
(3, 'Folded Length', '555mm', 'Specifications', 40),
(3, 'Load Capacity', '12kg', 'Specifications', 50),
(3, 'Leg Angle', '3 leg angle positions (23°, 50°, 80°)', 'Specifications', 60),

-- Features Group
(3, 'Leg Locks', 'Twist-lock mechanism', 'Features', 0),
(3, 'Feet', 'Spiked feet with rubber covers', 'Features', 10),
(3, 'Center Column', 'Reversible for low-angle shots', 'Features', 20),
(3, 'Quick Release', 'Arca-Swiss compatible', 'Features', 30),

-- Physical Group
(3, 'Weight', '1.8kg', 'Physical', 0),
(3, 'Carrying Case', 'Padded carrying case included', 'Physical', 10);

-- Product 4: Flash Unit Specifications
INSERT INTO product_specifications (product_id, spec_name, spec_value, spec_group, display_order) VALUES
-- Flash Specifications
(4, 'Guide Number', '60 (ISO 100, 200mm zoom)', 'Flash Performance', 0),
(4, 'Zoom Range', '24-200mm (14mm with wide panel)', 'Flash Performance', 10),
(4, 'Power Output', '1/1 to 1/256 in 1/3 stops', 'Flash Performance', 20),
(4, 'Recycle Time', '0.1-2.6 seconds', 'Flash Performance', 30),
(4, 'Flash Duration', '1/1000 to 1/35700 sec', 'Flash Performance', 40),

-- Features
(4, 'TTL Modes', 'i-TTL, Manual, Multi', 'Features', 0),
(4, 'High Speed Sync', 'Up to 1/8000 sec', 'Features', 10),
(4, 'Wireless Control', 'Master/Remote capability', 'Features', 20),
(4, 'Modeling Light', 'LED modeling light', 'Features', 30),

-- Power & Build
(4, 'Power Source', '4x AA batteries', 'Power & Build', 0),
(4, 'Battery Life', '200-1500 flashes', 'Power & Build', 10),
(4, 'Dimensions', '73 x 137 x 103mm', 'Power & Build', 20),
(4, 'Weight', '420g (without batteries)', 'Power & Build', 30);

-- Product 5: Memory Card Specifications
INSERT INTO product_specifications (product_id, spec_name, spec_value, spec_group, display_order) VALUES
-- Performance
(5, 'Capacity', '128GB', 'Performance', 0),
(5, 'Speed Class', 'UHS-II, U3, V90', 'Performance', 10),
(5, 'Read Speed', 'Up to 300 MB/s', 'Performance', 20),
(5, 'Write Speed', 'Up to 260 MB/s', 'Performance', 30),
(5, 'Video Speed', 'V90 (90 MB/s sustained)', 'Performance', 40),

-- Compatibility
(5, 'Format', 'SDXC', 'Compatibility', 0),
(5, 'File System', 'exFAT', 'Compatibility', 10),
(5, 'Temperature Range', '-25°C to +85°C', 'Compatibility', 20),

-- Features
(5, 'Durability', 'Waterproof, shockproof, X-ray proof', 'Features', 0),
(5, 'Warranty', '10-year limited warranty', 'Features', 10);

-- Product 6: Camera Bag Specifications (Ungrouped specifications)
INSERT INTO product_specifications (product_id, spec_name, spec_value, spec_group, display_order) VALUES
(6, 'Material', 'Water-resistant nylon with leather accents', NULL, 0),
(6, 'Dimensions', '45 x 35 x 20cm', NULL, 10),
(6, 'Weight', '1.2kg', NULL, 20),
(6, 'Camera Compartment', 'Fits 1 DSLR + 3-4 lenses', NULL, 30),
(6, 'Laptop Compartment', 'Up to 15-inch laptop', NULL, 40),
(6, 'Pockets', '8 interior and exterior pockets', NULL, 50),
(6, 'Carrying Options', 'Shoulder strap and backpack straps', NULL, 60),
(6, 'Color Options', 'Black, Brown, Olive Green', NULL, 70);

-- Product 7: Lens Filter Specifications
INSERT INTO product_specifications (product_id, spec_name, spec_value, spec_group, display_order) VALUES
-- Filter Specs
(7, 'Filter Type', 'Circular Polarizing Filter', 'Filter Specifications', 0),
(7, 'Thread Size', '77mm', 'Filter Specifications', 10),
(7, 'Glass Type', 'Multi-coated optical glass', 'Filter Specifications', 20),
(7, 'Polarization', '99.9% polarization efficiency', 'Filter Specifications', 30),
(7, 'Light Reduction', '1.5 stops', 'Filter Specifications', 40),

-- Build Quality
(7, 'Frame Material', 'Aluminum alloy', 'Build Quality', 0),
(7, 'Coating', '16-layer multi-coating', 'Build Quality', 10),
(7, 'Thickness', '7.5mm', 'Build Quality', 20),
(7, 'Front Thread', '77mm for filter stacking', 'Build Quality', 30);

-- Product 8: Studio Light Specifications
INSERT INTO product_specifications (product_id, spec_name, spec_value, spec_group, display_order) VALUES
-- Light Performance
(8, 'Power Output', '500W', 'Light Performance', 0),
(8, 'Color Temperature', '5600K ±200K', 'Light Performance', 10),
(8, 'CRI Rating', '>95', 'Light Performance', 20),
(8, 'Beam Angle', '120° (with standard reflector)', 'Light Performance', 30),
(8, 'Dimming Range', '10-100% stepless', 'Light Performance', 40),

-- Control Features
(8, 'Control Methods', 'Manual, DMX, Wireless app', 'Control Features', 0),
(8, 'Display', 'LCD display with menu system', 'Control Features', 10),
(8, 'Cooling', 'Silent fan cooling system', 'Control Features', 20),

-- Physical Build
(8, 'Dimensions', '320 x 180 x 280mm', 'Physical Build', 0),
(8, 'Weight', '4.2kg', 'Physical Build', 10),
(8, 'Mount', 'Bowens S-type', 'Physical Build', 20),
(8, 'Power Input', 'AC 100-240V, 50/60Hz', 'Physical Build', 30);

-- Product 9: Drone Camera Specifications
INSERT INTO product_specifications (product_id, spec_name, spec_value, spec_group, display_order) VALUES
-- Camera System
(9, 'Camera Resolution', '20MP 1-inch CMOS', 'Camera System', 0),
(9, 'Lens', 'Fixed f/2.8-f/11 aperture', 'Camera System', 10),
(9, 'Focal Length', '24mm (35mm equivalent)', 'Camera System', 20),
(9, 'ISO Range', '100-6400', 'Camera System', 30),
(9, 'Shutter Speed', '8-1/8000 sec', 'Camera System', 40),

-- Video Capabilities
(9, 'Video Resolution', '4K at 60fps', 'Video Capabilities', 0),
(9, 'Video Codec', 'H.264/H.265', 'Video Capabilities', 10),
(9, 'Bitrate', 'Up to 150 Mbps', 'Video Capabilities', 20),
(9, 'Gimbal', '3-axis mechanical gimbal', 'Video Capabilities', 30),

-- Flight Performance
(9, 'Max Flight Time', '34 minutes', 'Flight Performance', 0),
(9, 'Max Speed', '72 km/h (Sport mode)', 'Flight Performance', 10),
(9, 'Max Altitude', '6000m (above sea level)', 'Flight Performance', 20),
(9, 'Wind Resistance', '12 m/s', 'Flight Performance', 30),
(9, 'Transmission Range', '15km (FCC)', 'Flight Performance', 40),

-- Dimensions & Weight
(9, 'Folded Size', '221 x 96 x 90mm', 'Dimensions & Weight', 0),
(9, 'Unfolded Size', '347 x 283 x 107mm', 'Dimensions & Weight', 10),
(9, 'Weight', '895g', 'Dimensions & Weight', 20);

-- Product 10: Action Camera Specifications
INSERT INTO product_specifications (product_id, spec_name, spec_value, spec_group, display_order) VALUES
-- Camera Performance
(10, 'Video Resolution', '5.3K at 60fps, 4K at 120fps', 'Camera Performance', 0),
(10, 'Photo Resolution', '27MP', 'Camera Performance', 10),
(10, 'Sensor', '1/1.9-inch CMOS', 'Camera Performance', 20),
(10, 'Lens', 'Ultra-wide f/1.8', 'Camera Performance', 30),
(10, 'Field of View', '156° (Ultra-wide)', 'Camera Performance', 40),

-- Stabilization
(10, 'Image Stabilization', 'HyperSmooth 5.0', 'Stabilization', 0),
(10, 'Horizon Leveling', '360° horizon lock', 'Stabilization', 10),

-- Durability
(10, 'Waterproof', '10m without housing', 'Durability', 0),
(10, 'Drop Protection', '2m drop protection', 'Durability', 10),
(10, 'Operating Temperature', '-10°C to +35°C', 'Durability', 20),

-- Connectivity & Power
(10, 'WiFi', '802.11ac dual-band', 'Connectivity & Power', 0),
(10, 'Bluetooth', 'Bluetooth 5.0', 'Connectivity & Power', 10),
(10, 'USB', 'USB-C', 'Connectivity & Power', 20),
(10, 'Battery Life', '1.5 hours (5.3K video)', 'Connectivity & Power', 30),
(10, 'Storage', 'microSD up to 1TB', 'Connectivity & Power', 40),

-- Physical
(10, 'Dimensions', '71 x 55 x 33.6mm', 'Physical', 0),
(10, 'Weight', '153g', 'Physical', 10);

-- Display sample data
SELECT 'Product Specifications Data Inserted Successfully' as Status;

-- Show specifications count by product
SELECT 
    product_id,
    COUNT(*) as total_specs,
    COUNT(DISTINCT spec_group) as unique_groups,
    GROUP_CONCAT(DISTINCT spec_group ORDER BY spec_group) as groups
FROM product_specifications 
GROUP BY product_id 
ORDER BY product_id;

-- Show specifications by group
SELECT 
    COALESCE(spec_group, 'Ungrouped') as group_name,
    COUNT(*) as spec_count,
    COUNT(DISTINCT product_id) as products_with_group
FROM product_specifications 
GROUP BY spec_group 
ORDER BY spec_count DESC;

-- Show sample specifications for first product (grouped)
SELECT 
    COALESCE(spec_group, 'Ungrouped') as group_name,
    spec_name,
    spec_value,
    display_order
FROM product_specifications 
WHERE product_id = 1
ORDER BY 
    CASE WHEN spec_group IS NULL THEN 0 ELSE 1 END,
    spec_group,
    display_order,
    spec_name;

-- Show display order distribution
SELECT 
    display_order,
    COUNT(*) as count
FROM product_specifications 
GROUP BY display_order 
ORDER BY display_order;

-- Show products with most specifications
SELECT 
    product_id,
    COUNT(*) as spec_count
FROM product_specifications 
GROUP BY product_id 
ORDER BY spec_count DESC 
LIMIT 5;