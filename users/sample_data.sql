-- Users API Sample Data
-- This file contains the table schema and sample data for the users table
-- Run this file to set up the users table with sample data

-- Database Configuration
SET NAMES utf8mb4;
SET FOREIGN_KEY_CHECKS = 0;

-- =====================================================
-- TABLE SCHEMA
-- =====================================================

-- Users Table
CREATE TABLE IF NOT EXISTS users (
  id INT PRIMARY KEY AUTO_INCREMENT,
  full_name VARCHAR(255) NOT NULL,
  email VARCHAR(255) NOT NULL UNIQUE,
  phone VARCHAR(20) UNIQUE,
  password_hash VARCHAR(255) NOT NULL,
  role ENUM('admin', 'employee') NOT NULL DEFAULT 'employee',
  is_active TINYINT(1) NOT NULL DEFAULT 1,
  last_login_at TIMESTAMP NULL,
  created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
  updated_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
  INDEX idx_users_email (email),
  INDEX idx_users_role (role),
  INDEX idx_users_active (is_active)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- =====================================================
-- SAMPLE DATA
-- =====================================================

-- Clear existing data
DELETE FROM users;
ALTER TABLE users AUTO_INCREMENT = 1;

-- Sample Users
-- Note: All passwords are hashed versions of 'password123'
-- In production, use strong, unique passwords

INSERT INTO users (full_name, email, phone, password_hash, role, is_active, last_login_at, created_at, updated_at) VALUES
-- Admin Users
('John Smith', 'john.smith@photostore.com', '+1234567890', '$2y$10$92IXUNpkjO0rOQ5byMi.Ye4oKoEa3Ro9llC/.og/at2.uheWG/igi', 'admin', 1, '2024-01-15 14:30:00', '2024-01-01 09:00:00', '2024-01-15 14:30:00'),
('Sarah Johnson', 'sarah.johnson@photostore.com', '+1234567891', '$2y$10$92IXUNpkjO0rOQ5byMi.Ye4oKoEa3Ro9llC/.og/at2.uheWG/igi', 'admin', 1, '2024-01-14 16:45:00', '2024-01-02 10:15:00', '2024-01-14 16:45:00'),
('Michael Brown', 'michael.brown@photostore.com', '+1234567892', '$2y$10$92IXUNpkjO0rOQ5byMi.Ye4oKoEa3Ro9llC/.og/at2.uheWG/igi', 'admin', 1, '2024-01-13 11:20:00', '2024-01-03 11:30:00', '2024-01-13 11:20:00'),

-- Employee Users (Active)
('Emily Davis', 'emily.davis@photostore.com', '+1234567893', '$2y$10$92IXUNpkjO0rOQ5byMi.Ye4oKoEa3Ro9llC/.og/at2.uheWG/igi', 'employee', 1, '2024-01-15 13:15:00', '2024-01-05 14:00:00', '2024-01-15 13:15:00'),
('David Wilson', 'david.wilson@photostore.com', '+1234567894', '$2y$10$92IXUNpkjO0rOQ5byMi.Ye4oKoEa3Ro9llC/.og/at2.uheWG/igi', 'employee', 1, '2024-01-15 12:45:00', '2024-01-06 15:30:00', '2024-01-15 12:45:00'),
('Lisa Anderson', 'lisa.anderson@photostore.com', '+1234567895', '$2y$10$92IXUNpkjO0rOQ5byMi.Ye4oKoEa3Ro9llC/.og/at2.uheWG/igi', 'employee', 1, '2024-01-14 17:30:00', '2024-01-07 16:45:00', '2024-01-14 17:30:00'),
('Robert Taylor', 'robert.taylor@photostore.com', '+1234567896', '$2y$10$92IXUNpkjO0rOQ5byMi.Ye4oKoEa3Ro9llC/.og/at2.uheWG/igi', 'employee', 1, '2024-01-15 10:20:00', '2024-01-08 09:15:00', '2024-01-15 10:20:00'),
('Jennifer Martinez', 'jennifer.martinez@photostore.com', '+1234567897', '$2y$10$92IXUNpkjO0rOQ5byMi.Ye4oKoEa3Ro9llC/.og/at2.uheWG/igi', 'employee', 1, '2024-01-15 09:45:00', '2024-01-09 10:30:00', '2024-01-15 09:45:00'),
('Christopher Lee', 'christopher.lee@photostore.com', '+1234567898', '$2y$10$92IXUNpkjO0rOQ5byMi.Ye4oKoEa3Ro9llC/.og/at2.uheWG/igi', 'employee', 1, '2024-01-14 15:10:00', '2024-01-10 11:45:00', '2024-01-14 15:10:00'),
('Amanda White', 'amanda.white@photostore.com', '+1234567899', '$2y$10$92IXUNpkjO0rOQ5byMi.Ye4oKoEa3Ro9llC/.og/at2.uheWG/igi', 'employee', 1, '2024-01-15 14:55:00', '2024-01-11 13:20:00', '2024-01-15 14:55:00'),

-- Employee Users (Recently Added, No Login Yet)
('Daniel Garcia', 'daniel.garcia@photostore.com', '+1234567800', '$2y$10$92IXUNpkjO0rOQ5byMi.Ye4oKoEa3Ro9llC/.og/at2.uheWG/igi', 'employee', 1, NULL, '2024-01-12 14:30:00', '2024-01-12 14:30:00'),
('Michelle Rodriguez', 'michelle.rodriguez@photostore.com', '+1234567801', '$2y$10$92IXUNpkjO0rOQ5byMi.Ye4oKoEa3Ro9llC/.og/at2.uheWG/igi', 'employee', 1, NULL, '2024-01-13 15:45:00', '2024-01-13 15:45:00'),
('Kevin Thompson', 'kevin.thompson@photostore.com', '+1234567802', '$2y$10$92IXUNpkjO0rOQ5byMi.Ye4oKoEa3Ro9llC/.og/at2.uheWG/igi', 'employee', 1, NULL, '2024-01-14 16:20:00', '2024-01-14 16:20:00'),

-- Inactive/Deactivated Users
('Former Employee One', 'former1@photostore.com', '+1234567803', '$2y$10$92IXUNpkjO0rOQ5byMi.Ye4oKoEa3Ro9llC/.og/at2.uheWG/igi', 'employee', 0, '2024-01-05 12:00:00', '2023-12-01 09:00:00', '2024-01-10 17:00:00'),
('Former Employee Two', 'former2@photostore.com', '+1234567804', '$2y$10$92IXUNpkjO0rOQ5byMi.Ye4oKoEa3Ro9llC/.og/at2.uheWG/igi', 'employee', 0, '2024-01-03 14:30:00', '2023-11-15 10:30:00', '2024-01-08 16:45:00'),

-- Users with No Phone Numbers
('Alex Johnson', 'alex.johnson@photostore.com', NULL, '$2y$10$92IXUNpkjO0rOQ5byMi.Ye4oKoEa3Ro9llC/.og/at2.uheWG/igi', 'employee', 1, '2024-01-14 13:25:00', '2024-01-11 12:15:00', '2024-01-14 13:25:00'),
('Taylor Smith', 'taylor.smith@photostore.com', NULL, '$2y$10$92IXUNpkjO0rOQ5byMi.Ye4oKoEa3Ro9llC/.og/at2.uheWG/igi', 'employee', 1, NULL, '2024-01-13 14:40:00', '2024-01-13 14:40:00'),

-- International Users
('Pierre Dubois', 'pierre.dubois@photostore.com', '+33123456789', '$2y$10$92IXUNpkjO0rOQ5byMi.Ye4oKoEa3Ro9llC/.og/at2.uheWG/igi', 'employee', 1, '2024-01-15 08:30:00', '2024-01-09 09:45:00', '2024-01-15 08:30:00'),
('Yuki Tanaka', 'yuki.tanaka@photostore.com', '+81987654321', '$2y$10$92IXUNpkjO0rOQ5byMi.Ye4oKoEa3Ro9llC/.og/at2.uheWG/igi', 'employee', 1, '2024-01-14 19:15:00', '2024-01-10 08:20:00', '2024-01-14 19:15:00'),
('Hans Mueller', 'hans.mueller@photostore.com', '+49123456789', '$2y$10$92IXUNpkjO0rOQ5byMi.Ye4oKoEa3Ro9llC/.og/at2.uheWG/igi', 'employee', 1, '2024-01-15 07:45:00', '2024-01-08 07:30:00', '2024-01-15 07:45:00'),

-- Test Users for Different Scenarios
('Test Admin', 'test.admin@photostore.com', '+1999999999', '$2y$10$92IXUNpkjO0rOQ5byMi.Ye4oKoEa3Ro9llC/.og/at2.uheWG/igi', 'admin', 1, NULL, '2024-01-15 18:00:00', '2024-01-15 18:00:00');

-- =====================================================
-- VERIFICATION QUERIES
-- =====================================================

-- Display all users
SELECT 
    id,
    full_name,
    email,
    phone,
    role,
    is_active,
    last_login_at,
    created_at,
    updated_at
FROM users
ORDER BY created_at DESC;

-- Count users by role
SELECT 
    role,
    COUNT(*) as user_count,
    SUM(is_active) as active_count,
    COUNT(*) - SUM(is_active) as inactive_count
FROM users
GROUP BY role
ORDER BY role;

-- Active users summary
SELECT 
    COUNT(*) as total_users,
    SUM(is_active) as active_users,
    COUNT(*) - SUM(is_active) as inactive_users,
    ROUND(SUM(is_active) * 100.0 / COUNT(*), 2) as active_percentage
FROM users;

-- Recent login activity
SELECT 
    full_name,
    email,
    role,
    last_login_at,
    CASE 
        WHEN last_login_at IS NULL THEN 'Never logged in'
        WHEN last_login_at >= DATE_SUB(NOW(), INTERVAL 1 DAY) THEN 'Today'
        WHEN last_login_at >= DATE_SUB(NOW(), INTERVAL 7 DAY) THEN 'This week'
        WHEN last_login_at >= DATE_SUB(NOW(), INTERVAL 30 DAY) THEN 'This month'
        ELSE 'More than a month ago'
    END as login_status
FROM users
WHERE is_active = 1
ORDER BY last_login_at DESC;

-- Users without phone numbers
SELECT 
    id,
    full_name,
    email,
    role,
    is_active,
    created_at
FROM users
WHERE phone IS NULL
ORDER BY created_at DESC;

-- Users by creation date
SELECT 
    DATE(created_at) as creation_date,
    COUNT(*) as users_created,
    SUM(CASE WHEN role = 'admin' THEN 1 ELSE 0 END) as admins,
    SUM(CASE WHEN role = 'employee' THEN 1 ELSE 0 END) as employees
FROM users
GROUP BY DATE(created_at)
ORDER BY creation_date DESC;

-- Search functionality test
-- Users with 'john' in name or email
SELECT 
    id,
    full_name,
    email,
    phone,
    role,
    is_active
FROM users
WHERE (full_name LIKE '%john%' OR email LIKE '%john%' OR phone LIKE '%john%')
ORDER BY full_name;

-- International users (non-US phone numbers)
SELECT 
    id,
    full_name,
    email,
    phone,
    role,
    is_active
FROM users
WHERE phone IS NOT NULL 
  AND phone NOT LIKE '+1%'
ORDER BY full_name;

-- Users who haven't logged in yet
SELECT 
    id,
    full_name,
    email,
    role,
    is_active,
    created_at,
    DATEDIFF(NOW(), created_at) as days_since_creation
FROM users
WHERE last_login_at IS NULL
  AND is_active = 1
ORDER BY created_at DESC;

-- Most recently updated users
SELECT 
    id,
    full_name,
    email,
    role,
    is_active,
    created_at,
    updated_at,
    CASE 
        WHEN updated_at > created_at THEN 'Modified'
        ELSE 'Never modified'
    END as modification_status
FROM users
ORDER BY updated_at DESC
LIMIT 10;

-- Role distribution analysis
SELECT 
    role,
    COUNT(*) as total,
    ROUND(COUNT(*) * 100.0 / (SELECT COUNT(*) FROM users), 2) as percentage,
    MIN(created_at) as first_user,
    MAX(created_at) as latest_user
FROM users
GROUP BY role;

-- =====================================================
-- SAMPLE API USAGE SCENARIOS
-- =====================================================

-- Scenario 1: Get all active employees
-- API Call: GET /get.php?role=employee&is_active=1
-- Expected: 13 active employees

-- Scenario 2: Search for users named 'John'
-- API Call: GET /get.php?q=john
-- Expected: John Smith, Alex Johnson, Taylor Smith

-- Scenario 3: Get all admins
-- API Call: GET /get.php?role=admin
-- Expected: 4 admin users (including test admin)

-- Scenario 4: Get inactive users
-- API Call: GET /get.php?is_active=0
-- Expected: 2 former employees

-- Scenario 5: Get users with pagination
-- API Call: GET /get.php?page=1&limit=5
-- Expected: First 5 users with pagination info

-- Scenario 6: Search by phone area
-- API Call: GET /get.php?q=+1234
-- Expected: All US users with phone numbers

-- =====================================================
-- PASSWORD INFORMATION
-- =====================================================

/*
All sample users have the password: 'password123'
The hash used is: $2y$10$92IXUNpkjO0rOQ5byMi.Ye4oKoEa3Ro9llC/.og/at2.uheWG/igi

For testing purposes, you can use these credentials:

Admin Users:
- john.smith@photostore.com / password123
- sarah.johnson@photostore.com / password123
- michael.brown@photostore.com / password123
- test.admin@photostore.com / password123

Employee Users:
- emily.davis@photostore.com / password123
- david.wilson@photostore.com / password123
- lisa.anderson@photostore.com / password123
- (and others...)

In production, always use strong, unique passwords!
*/

-- =====================================================
-- NOTES
-- =====================================================

/*
Sample Data Summary:
- 21 total users
- 4 admin users (including 1 test admin)
- 17 employee users
- 19 active users
- 2 inactive users
- 3 users who haven't logged in yet
- 2 users without phone numbers
- 3 international users
- Realistic timestamps and login patterns
- Variety of names and email formats

Data Distribution:
- Admin: 19% (4/21)
- Employee: 81% (17/21)
- Active: 90% (19/21)
- Inactive: 10% (2/21)
- With Phone: 86% (18/21)
- Without Phone: 14% (3/21)

Use Cases Covered:
- User authentication and authorization
- Role-based access control
- User search and filtering
- Account management (active/inactive)
- International user support
- Audit trail with timestamps
- Password security demonstration
- Pagination testing
- Data validation scenarios
*/

-- Re-enable foreign key checks
SET FOREIGN_KEY_CHECKS = 1;