-- Categories Sample Data
-- This file contains the table schema and sample data for the categories table

-- Create the categories table
CREATE TABLE IF NOT EXISTS categories (
  id INT PRIMARY KEY AUTO_INCREMENT,
  name VARCHAR(255) NOT NULL,
  slug VARCHAR(255) UNIQUE NOT NULL,
  image_url VARCHAR(500),
  is_active BOOLEAN DEFAULT TRUE,
  created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
  updated_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- Insert sample categories data
INSERT INTO categories (name, slug, image_url, is_active) VALUES
('Cameras', 'cameras', '/uploads/categories/cameras.jpg', 1),
('Lenses', 'lenses', '/uploads/categories/lenses.jpg', 1),
('Tripods', 'tripods', '/uploads/categories/tripods.jpg', 1),
('Lighting Equipment', 'lighting-equipment', '/uploads/categories/lighting.jpg', 1),
('Camera Bags', 'camera-bags', '/uploads/categories/bags.jpg', 1),
('Memory Cards', 'memory-cards', '/uploads/categories/memory-cards.jpg', 1),
('Batteries & Chargers', 'batteries-chargers', '/uploads/categories/batteries.jpg', 1),
('Filters', 'filters', '/uploads/categories/filters.jpg', 1),
('Flash & Studio', 'flash-studio', '/uploads/categories/flash.jpg', 1),
('Video Equipment', 'video-equipment', '/uploads/categories/video.jpg', 1),
('Drones', 'drones', '/uploads/categories/drones.jpg', 1),
('Action Cameras', 'action-cameras', '/uploads/categories/action-cameras.jpg', 1),
('Printers', 'printers', '/uploads/categories/printers.jpg', 1),
('Software', 'software', '/uploads/categories/software.jpg', 1),
('Accessories', 'accessories', '/uploads/categories/accessories.jpg', 1);

-- Add some inactive categories for testing
INSERT INTO categories (name, slug, image_url, is_active) VALUES
('Discontinued Items', 'discontinued-items', '/uploads/categories/discontinued.jpg', 0),
('Test Category', 'test-category', NULL, 0);

-- Display inserted data
SELECT * FROM categories ORDER BY id;