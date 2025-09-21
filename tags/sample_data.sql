-- Tags Sample Data
-- This file contains the table schema and sample data for the tags API

-- Optional: Create database and set encoding
CREATE DATABASE IF NOT EXISTS photography_store 
  CHARACTER SET utf8mb4 
  COLLATE utf8mb4_unicode_ci;
USE photography_store;

-- Tags Table Schema
CREATE TABLE IF NOT EXISTS tags (
  id INT PRIMARY KEY AUTO_INCREMENT,
  name VARCHAR(100) NOT NULL UNIQUE,
  slug VARCHAR(100) UNIQUE NOT NULL,
  created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
  
  -- Index for better performance
  INDEX idx_tags_name (name),
  INDEX idx_tags_slug (slug)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- Sample Data for Tags
-- Photography Equipment Tags

-- Camera Type Tags
INSERT INTO tags (name, slug) VALUES
('DSLR', 'dslr'),
('Mirrorless', 'mirrorless'),
('Point and Shoot', 'point-and-shoot'),
('Action Camera', 'action-camera'),
('Instant Camera', 'instant-camera'),
('Film Camera', 'film-camera'),
('Medium Format', 'medium-format'),
('Drone Camera', 'drone-camera');

-- Sensor Size Tags
INSERT INTO tags (name, slug) VALUES
('Full Frame', 'full-frame'),
('APS-C', 'aps-c'),
('Micro Four Thirds', 'micro-four-thirds'),
('1-inch Sensor', '1-inch-sensor'),
('Super 35mm', 'super-35mm');

-- Skill Level Tags
INSERT INTO tags (name, slug) VALUES
('Professional', 'professional'),
('Semi-Professional', 'semi-professional'),
('Enthusiast', 'enthusiast'),
('Entry Level', 'entry-level'),
('Beginner Friendly', 'beginner-friendly');

-- Lens Type Tags
INSERT INTO tags (name, slug) VALUES
('Prime Lens', 'prime-lens'),
('Zoom Lens', 'zoom-lens'),
('Telephoto', 'telephoto'),
('Wide Angle', 'wide-angle'),
('Ultra Wide', 'ultra-wide'),
('Macro Lens', 'macro-lens'),
('Fish Eye', 'fish-eye'),
('Portrait Lens', 'portrait-lens'),
('Standard Lens', 'standard-lens');

-- Lens Features Tags
INSERT INTO tags (name, slug) VALUES
('Image Stabilization', 'image-stabilization'),
('Fast Aperture', 'fast-aperture'),
('Weather Sealed', 'weather-sealed'),
('Silent Motor', 'silent-motor'),
('Manual Focus', 'manual-focus'),
('Autofocus', 'autofocus');

-- Photography Style Tags
INSERT INTO tags (name, slug) VALUES
('Portrait Photography', 'portrait-photography'),
('Landscape Photography', 'landscape-photography'),
('Street Photography', 'street-photography'),
('Wildlife Photography', 'wildlife-photography'),
('Sports Photography', 'sports-photography'),
('Wedding Photography', 'wedding-photography'),
('Macro Photography', 'macro-photography'),
('Astrophotography', 'astrophotography'),
('Travel Photography', 'travel-photography'),
('Studio Photography', 'studio-photography');

-- Video Features Tags
INSERT INTO tags (name, slug) VALUES
('4K Video', '4k-video'),
('8K Video', '8k-video'),
('Slow Motion', 'slow-motion'),
('Time Lapse', 'time-lapse'),
('Live Streaming', 'live-streaming'),
('Video Stabilization', 'video-stabilization'),
('External Recording', 'external-recording');

-- Build Quality Tags
INSERT INTO tags (name, slug) VALUES
('Lightweight', 'lightweight'),
('Compact', 'compact'),
('Durable', 'durable'),
('Metal Construction', 'metal-construction'),
('Carbon Fiber', 'carbon-fiber'),
('Magnesium Alloy', 'magnesium-alloy'),
('Dust Resistant', 'dust-resistant'),
('Water Resistant', 'water-resistant'),
('Freeze Proof', 'freeze-proof');

-- Connectivity Tags
INSERT INTO tags (name, slug) VALUES
('WiFi', 'wifi'),
('Bluetooth', 'bluetooth'),
('NFC', 'nfc'),
('USB-C', 'usb-c'),
('HDMI Output', 'hdmi-output'),
('Wireless Transfer', 'wireless-transfer'),
('Remote Control', 'remote-control'),
('App Control', 'app-control');

-- Accessory Type Tags
INSERT INTO tags (name, slug) VALUES
('Tripod', 'tripod'),
('Monopod', 'monopod'),
('Camera Bag', 'camera-bag'),
('Memory Card', 'memory-card'),
('Battery', 'battery'),
('Charger', 'charger'),
('Flash', 'flash'),
('Filter', 'filter'),
('Lens Hood', 'lens-hood'),
('Camera Strap', 'camera-strap');

-- Filter Type Tags
INSERT INTO tags (name, slug) VALUES
('UV Filter', 'uv-filter'),
('Polarizing Filter', 'polarizing-filter'),
('ND Filter', 'nd-filter'),
('Graduated Filter', 'graduated-filter'),
('Color Filter', 'color-filter');

-- Lighting Tags
INSERT INTO tags (name, slug) VALUES
('Studio Light', 'studio-light'),
('Continuous Light', 'continuous-light'),
('Strobe Light', 'strobe-light'),
('LED Panel', 'led-panel'),
('Ring Light', 'ring-light'),
('Softbox', 'softbox'),
('Umbrella', 'umbrella'),
('Reflector', 'reflector');

-- Brand Category Tags
INSERT INTO tags (name, slug) VALUES
('Japanese Brand', 'japanese-brand'),
('German Brand', 'german-brand'),
('American Brand', 'american-brand'),
('Swiss Brand', 'swiss-brand'),
('Premium Brand', 'premium-brand'),
('Budget Brand', 'budget-brand');

-- Usage Context Tags
INSERT INTO tags (name, slug) VALUES
('Indoor Use', 'indoor-use'),
('Outdoor Use', 'outdoor-use'),
('Studio Use', 'studio-use'),
('Travel Friendly', 'travel-friendly'),
('Event Photography', 'event-photography'),
('Commercial Use', 'commercial-use'),
('Personal Use', 'personal-use'),
('Educational', 'educational');

-- Special Features Tags
INSERT INTO tags (name, slug) VALUES
('In Body Stabilization', 'in-body-stabilization'),
('Dual Card Slots', 'dual-card-slots'),
('Articulating Screen', 'articulating-screen'),
('Touch Screen', 'touch-screen'),
('Electronic Viewfinder', 'electronic-viewfinder'),
('Optical Viewfinder', 'optical-viewfinder'),
('Silent Shooting', 'silent-shooting'),
('High ISO Performance', 'high-iso-performance'),
('Fast Burst Rate', 'fast-burst-rate'),
('Long Battery Life', 'long-battery-life');

-- Price Range Tags
INSERT INTO tags (name, slug) VALUES
('Budget Friendly', 'budget-friendly'),
('Mid Range', 'mid-range'),
('High End', 'high-end'),
('Luxury', 'luxury'),
('Value for Money', 'value-for-money'),
('Investment Grade', 'investment-grade');

-- Condition Tags (for used equipment)
INSERT INTO tags (name, slug) VALUES
('New', 'new'),
('Like New', 'like-new'),
('Excellent', 'excellent'),
('Very Good', 'very-good'),
('Good', 'good'),
('Fair', 'fair'),
('Refurbished', 'refurbished'),
('Open Box', 'open-box');

-- Display sample data
SELECT 'Tags Data Inserted Successfully' as Status;

-- Show total tags count
SELECT COUNT(*) as total_tags FROM tags;

-- Show tags by category (based on naming patterns)
SELECT 
    CASE 
        WHEN name LIKE '%Camera%' OR name IN ('DSLR', 'Mirrorless', 'Point and Shoot', 'Action Camera', 'Instant Camera', 'Film Camera', 'Medium Format', 'Drone Camera') THEN 'Camera Types'
        WHEN name LIKE '%Lens%' OR name IN ('Prime Lens', 'Zoom Lens', 'Telephoto', 'Wide Angle', 'Ultra Wide', 'Macro Lens', 'Fish Eye', 'Portrait Lens', 'Standard Lens') THEN 'Lens Types'
        WHEN name LIKE '%Photography%' THEN 'Photography Styles'
        WHEN name LIKE '%Video%' OR name IN ('Slow Motion', 'Time Lapse', 'Live Streaming', 'Video Stabilization', 'External Recording') THEN 'Video Features'
        WHEN name LIKE '%Filter%' THEN 'Filters'
        WHEN name LIKE '%Light%' OR name IN ('Strobe Light', 'LED Panel', 'Ring Light', 'Softbox', 'Umbrella', 'Reflector') THEN 'Lighting'
        WHEN name IN ('Professional', 'Semi-Professional', 'Enthusiast', 'Entry Level', 'Beginner Friendly') THEN 'Skill Levels'
        WHEN name IN ('Full Frame', 'APS-C', 'Micro Four Thirds', '1-inch Sensor', 'Super 35mm') THEN 'Sensor Sizes'
        WHEN name LIKE '%Brand%' THEN 'Brand Categories'
        WHEN name IN ('Budget Friendly', 'Mid Range', 'High End', 'Luxury', 'Value for Money', 'Investment Grade') THEN 'Price Ranges'
        WHEN name IN ('New', 'Like New', 'Excellent', 'Very Good', 'Good', 'Fair', 'Refurbished', 'Open Box') THEN 'Conditions'
        ELSE 'Other Features'
    END as category,
    COUNT(*) as tag_count
FROM tags 
GROUP BY category 
ORDER BY tag_count DESC;

-- Show sample tags from each major category
SELECT 'Camera Type Tags:' as category;
SELECT name, slug FROM tags WHERE name IN ('DSLR', 'Mirrorless', 'Action Camera', 'Drone Camera') ORDER BY name;

SELECT 'Lens Type Tags:' as category;
SELECT name, slug FROM tags WHERE name LIKE '%Lens%' LIMIT 5;

SELECT 'Photography Style Tags:' as category;
SELECT name, slug FROM tags WHERE name LIKE '%Photography%' LIMIT 5;

SELECT 'Feature Tags:' as category;
SELECT name, slug FROM tags WHERE name IN ('Weather Sealed', 'Image Stabilization', 'Fast Aperture', 'WiFi', 'Touch Screen') ORDER BY name;

-- Show tags with longest and shortest names
SELECT 'Longest Tag Names:' as info;
SELECT name, CHAR_LENGTH(name) as length FROM tags ORDER BY length DESC LIMIT 5;

SELECT 'Shortest Tag Names:' as info;
SELECT name, CHAR_LENGTH(name) as length FROM tags ORDER BY length ASC LIMIT 5;

-- Show slug generation examples
SELECT 'Slug Generation Examples:' as info;
SELECT name, slug FROM tags WHERE name != slug ORDER BY RAND() LIMIT 10;