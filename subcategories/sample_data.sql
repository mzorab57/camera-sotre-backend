-- Subcategories Sample Data
-- This file contains the table schema and sample data for the subcategories table

-- Create the subcategories table
CREATE TABLE IF NOT EXISTS subcategories (
  id INT PRIMARY KEY AUTO_INCREMENT,
  category_id INT NOT NULL,
  name VARCHAR(255) NOT NULL,
  slug VARCHAR(255) UNIQUE NOT NULL,
  type ENUM('photography', 'videography', 'both') DEFAULT 'both',
  image_url VARCHAR(500),
  is_active BOOLEAN DEFAULT TRUE,
  created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
  updated_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
  FOREIGN KEY (category_id) REFERENCES categories(id) ON DELETE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- Insert sample subcategories data
-- Note: Make sure categories exist first (category_id references)

-- Camera subcategories (category_id = 1)
INSERT INTO subcategories (category_id, name, slug, type, image_url, is_active) VALUES
(1, 'DSLR Cameras', 'dslr-cameras', 'photography', '/uploads/subcategories/dslr.jpg', 1),
(1, 'Mirrorless Cameras', 'mirrorless-cameras', 'both', '/uploads/subcategories/mirrorless.jpg', 1),
(1, 'Film Cameras', 'film-cameras', 'photography', '/uploads/subcategories/film.jpg', 1),
(1, 'Instant Cameras', 'instant-cameras', 'photography', '/uploads/subcategories/instant.jpg', 1),
(1, 'Medium Format', 'medium-format', 'photography', '/uploads/subcategories/medium-format.jpg', 1);

-- Lens subcategories (category_id = 2)
INSERT INTO subcategories (category_id, name, slug, type, image_url, is_active) VALUES
(2, 'Prime Lenses', 'prime-lenses', 'both', '/uploads/subcategories/prime.jpg', 1),
(2, 'Zoom Lenses', 'zoom-lenses', 'both', '/uploads/subcategories/zoom.jpg', 1),
(2, 'Macro Lenses', 'macro-lenses', 'photography', '/uploads/subcategories/macro.jpg', 1),
(2, 'Telephoto Lenses', 'telephoto-lenses', 'both', '/uploads/subcategories/telephoto.jpg', 1),
(2, 'Wide Angle Lenses', 'wide-angle-lenses', 'both', '/uploads/subcategories/wide-angle.jpg', 1),
(2, 'Fisheye Lenses', 'fisheye-lenses', 'both', '/uploads/subcategories/fisheye.jpg', 1);

-- Tripod subcategories (category_id = 3)
INSERT INTO subcategories (category_id, name, slug, type, image_url, is_active) VALUES
(3, 'Carbon Fiber Tripods', 'carbon-fiber-tripods', 'both', '/uploads/subcategories/carbon-tripods.jpg', 1),
(3, 'Aluminum Tripods', 'aluminum-tripods', 'both', '/uploads/subcategories/aluminum-tripods.jpg', 1),
(3, 'Travel Tripods', 'travel-tripods', 'both', '/uploads/subcategories/travel-tripods.jpg', 1),
(3, 'Video Tripods', 'video-tripods', 'videography', '/uploads/subcategories/video-tripods.jpg', 1),
(3, 'Monopods', 'monopods', 'both', '/uploads/subcategories/monopods.jpg', 1);

-- Lighting Equipment subcategories (category_id = 4)
INSERT INTO subcategories (category_id, name, slug, type, image_url, is_active) VALUES
(4, 'Studio Strobes', 'studio-strobes', 'photography', '/uploads/subcategories/studio-strobes.jpg', 1),
(4, 'Continuous Lighting', 'continuous-lighting', 'both', '/uploads/subcategories/continuous.jpg', 1),
(4, 'LED Panels', 'led-panels', 'both', '/uploads/subcategories/led-panels.jpg', 1),
(4, 'Ring Lights', 'ring-lights', 'both', '/uploads/subcategories/ring-lights.jpg', 1),
(4, 'Light Modifiers', 'light-modifiers', 'both', '/uploads/subcategories/modifiers.jpg', 1);

-- Camera Bag subcategories (category_id = 5)
INSERT INTO subcategories (category_id, name, slug, type, image_url, is_active) VALUES
(5, 'Backpacks', 'camera-backpacks', 'both', '/uploads/subcategories/backpacks.jpg', 1),
(5, 'Shoulder Bags', 'shoulder-bags', 'both', '/uploads/subcategories/shoulder-bags.jpg', 1),
(5, 'Rolling Cases', 'rolling-cases', 'both', '/uploads/subcategories/rolling-cases.jpg', 1),
(5, 'Lens Cases', 'lens-cases', 'both', '/uploads/subcategories/lens-cases.jpg', 1),
(5, 'Waterproof Cases', 'waterproof-cases', 'both', '/uploads/subcategories/waterproof.jpg', 1);

-- Memory Card subcategories (category_id = 6)
INSERT INTO subcategories (category_id, name, slug, type, image_url, is_active) VALUES
(6, 'SD Cards', 'sd-cards', 'both', '/uploads/subcategories/sd-cards.jpg', 1),
(6, 'CF Cards', 'cf-cards', 'both', '/uploads/subcategories/cf-cards.jpg', 1),
(6, 'MicroSD Cards', 'microsd-cards', 'both', '/uploads/subcategories/microsd.jpg', 1),
(6, 'XQD Cards', 'xqd-cards', 'both', '/uploads/subcategories/xqd.jpg', 1),
(6, 'CFexpress Cards', 'cfexpress-cards', 'both', '/uploads/subcategories/cfexpress.jpg', 1);

-- Video Equipment subcategories (category_id = 10)
INSERT INTO subcategories (category_id, name, slug, type, image_url, is_active) VALUES
(10, 'Gimbals', 'gimbals', 'videography', '/uploads/subcategories/gimbals.jpg', 1),
(10, 'Video Microphones', 'video-microphones', 'videography', '/uploads/subcategories/video-mics.jpg', 1),
(10, 'Video Monitors', 'video-monitors', 'videography', '/uploads/subcategories/monitors.jpg', 1),
(10, 'Sliders & Dollies', 'sliders-dollies', 'videography', '/uploads/subcategories/sliders.jpg', 1),
(10, 'Video Recorders', 'video-recorders', 'videography', '/uploads/subcategories/recorders.jpg', 1);

-- Action Camera subcategories (category_id = 12)
INSERT INTO subcategories (category_id, name, slug, type, image_url, is_active) VALUES
(12, 'GoPro Cameras', 'gopro-cameras', 'both', '/uploads/subcategories/gopro.jpg', 1),
(12, 'Action Camera Mounts', 'action-mounts', 'both', '/uploads/subcategories/action-mounts.jpg', 1),
(12, 'Underwater Housing', 'underwater-housing', 'both', '/uploads/subcategories/underwater.jpg', 1),
(12, 'Action Camera Accessories', 'action-accessories', 'both', '/uploads/subcategories/action-accessories.jpg', 1);

-- Add some inactive subcategories for testing
INSERT INTO subcategories (category_id, name, slug, type, image_url, is_active) VALUES
(1, 'Discontinued Camera Type', 'discontinued-camera', 'photography', NULL, 0),
(2, 'Test Lens Category', 'test-lens', 'both', NULL, 0);

-- Display inserted data
SELECT s.*, c.name as category_name 
FROM subcategories s 
LEFT JOIN categories c ON s.category_id = c.id 
ORDER BY s.category_id, s.id;