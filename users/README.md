# Users API Documentation

## Overview
The Users API manages user accounts in the photography store system. It provides endpoints for creating, reading, updating, and deactivating/restoring user accounts with role-based access control.

## Base URL
```
http://localhost/api/users/
```

## Database Schema
```sql
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
```

## Authentication
All endpoints require **Admin** authentication via the `protect_admin.php` middleware. Only administrators can manage user accounts.

## Endpoints

### 1. GET Users
**Endpoint:** `GET /get.php`
**Authentication:** Admin required

#### Get Single User
```
GET /get.php?id=1
```

#### Get All Users with Pagination
```
GET /get.php?page=1&limit=20
```

#### Filter Users
```
GET /get.php?role=admin&is_active=1&q=john&page=1&limit=10
```

#### Query Parameters
- `id` (integer): Get specific user by ID
- `page` (integer, default: 1): Page number for pagination
- `limit` (integer, default: 20, max: 100): Number of users per page
- `role` (string): Filter by role (`admin` or `employee`)
- `is_active` (boolean): Filter by active status (1 for active, 0 for inactive)
- `q` (string): Search in full_name, email, or phone fields

#### Response Format (Single User)
```json
{
  "success": true,
  "data": {
    "id": 1,
    "full_name": "John Doe",
    "email": "john.doe@example.com",
    "phone": "+1234567890",
    "role": "admin",
    "is_active": 1,
    "last_login_at": "2024-01-15 14:30:00",
    "created_at": "2024-01-10 09:00:00",
    "updated_at": "2024-01-15 14:30:00"
  }
}
```

#### Response Format (Multiple Users)
```json
{
  "success": true,
  "data": [
    {
      "id": 1,
      "full_name": "John Doe",
      "email": "john.doe@example.com",
      "phone": "+1234567890",
      "role": "admin",
      "is_active": 1,
      "last_login_at": "2024-01-15 14:30:00",
      "created_at": "2024-01-10 09:00:00",
      "updated_at": "2024-01-15 14:30:00"
    },
    {
      "id": 2,
      "full_name": "Jane Smith",
      "email": "jane.smith@example.com",
      "phone": "+1234567891",
      "role": "employee",
      "is_active": 1,
      "last_login_at": null,
      "created_at": "2024-01-12 10:15:00",
      "updated_at": "2024-01-12 10:15:00"
    }
  ],
  "pagination": {
    "page": 1,
    "limit": 20,
    "total": 25,
    "pages": 2
  }
}
```

### 2. Create User
**Endpoint:** `POST /create.php`
**Authentication:** Admin required

#### Request Body
```json
{
  "full_name": "John Doe",
  "email": "john.doe@example.com",
  "password": "SecurePassword123!",
  "phone": "+1234567890",
  "role": "employee",
  "is_active": 1
}
```

#### Request Parameters
- `full_name` (string, required): User's full name
- `email` (string, required): Valid email address (must be unique)
- `password` (string, required): User's password (will be hashed)
- `phone` (string, optional): Phone number (must be unique if provided)
- `role` (string, optional, default: "employee"): User role (`admin` or `employee`)
- `is_active` (boolean, optional, default: true): Account active status

#### Response Format
```json
{
  "success": true,
  "data": {
    "id": 3,
    "full_name": "John Doe",
    "email": "john.doe@example.com",
    "phone": "+1234567890",
    "role": "employee",
    "is_active": 1,
    "last_login_at": null,
    "created_at": "2024-01-15 16:45:00",
    "updated_at": "2024-01-15 16:45:00"
  }
}
```

### 3. Update User
**Endpoint:** `POST|PUT|PATCH /update.php`
**Authentication:** Admin required

#### Update via URL Parameter
```
PUT /update.php?id=1
```

#### Update via Request Body
```json
{
  "id": 1,
  "full_name": "John Updated Doe",
  "phone": "+1234567899",
  "role": "admin",
  "is_active": 1,
  "password": "NewSecurePassword123!"
}
```

#### Request Parameters
- `id` (integer, required): User ID (via URL parameter or request body)
- `full_name` (string, optional): Update user's full name
- `phone` (string, optional): Update phone number (set to empty string to clear)
- `role` (string, optional): Update role (`admin` or `employee`)
- `is_active` (boolean, optional): Update active status
- `password` (string, optional): Update password (will be hashed)
- `email` (forbidden): Email cannot be changed

**Note:** At least one field must be provided for update

#### Response Format
```json
{
  "success": true,
  "data": {
    "id": 1,
    "full_name": "John Updated Doe",
    "email": "john.doe@example.com",
    "phone": "+1234567899",
    "role": "admin",
    "is_active": 1,
    "last_login_at": "2024-01-15 14:30:00",
    "created_at": "2024-01-10 09:00:00",
    "updated_at": "2024-01-15 16:50:00"
  }
}
```

### 4. Deactivate/Restore User
**Endpoint:** `POST|DELETE /delete.php`
**Authentication:** Admin required

#### Deactivate User (Soft Delete)
```
DELETE /delete.php?id=1
```

#### Restore User
```
POST /delete.php?id=1&restore=1
```

#### Request Body Alternative
```json
{
  "id": 1,
  "restore": 0
}
```

#### Request Parameters
- `id` (integer, required): User ID to deactivate/restore
- `restore` (boolean, optional, default: false): Set to true to restore user

#### Response Format
```json
{
  "success": true,
  "message": "User deactivated (is_active=0)",
  "data": {
    "id": 1,
    "full_name": "John Doe",
    "email": "john.doe@example.com",
    "phone": "+1234567890",
    "role": "admin",
    "is_active": 0,
    "last_login_at": "2024-01-15 14:30:00",
    "created_at": "2024-01-10 09:00:00",
    "updated_at": "2024-01-15 17:00:00"
  }
}
```

#### Response Format (Restore)
```json
{
  "success": true,
  "message": "User restored (is_active=1)",
  "data": {
    "id": 1,
    "full_name": "John Doe",
    "email": "john.doe@example.com",
    "phone": "+1234567890",
    "role": "admin",
    "is_active": 1,
    "last_login_at": "2024-01-15 14:30:00",
    "created_at": "2024-01-10 09:00:00",
    "updated_at": "2024-01-15 17:05:00"
  }
}
```

## Error Codes
- `400` - Bad Request (missing required fields, no fields to update)
- `401` - Unauthorized (authentication required)
- `403` - Forbidden (insufficient permissions)
- `404` - Not Found (user not found)
- `405` - Method Not Allowed
- `409` - Conflict (duplicate email or phone)
- `422` - Unprocessable Entity (invalid email, role, or empty required fields)
- `500` - Internal Server Error (database errors)

## Features

### Security
- Password hashing using PHP's `password_hash()` with `PASSWORD_DEFAULT`
- Email validation using PHP's `FILTER_VALIDATE_EMAIL`
- Role-based access control (admin-only access)
- Protection against email modification

### Data Integrity
- Unique constraints on email and phone
- Role validation (only `admin` or `employee` allowed)
- Proper handling of optional fields
- Automatic timestamp management

### Soft Delete System
- Users are deactivated rather than permanently deleted
- Restore functionality for reactivating users
- Maintains data integrity and audit trail

### Search and Filtering
- Full-text search across name, email, and phone
- Role-based filtering
- Active status filtering
- Pagination support for large datasets

### Flexible Updates
- Partial updates (only specified fields)
- Password updates with automatic hashing
- Phone number clearing capability
- Role promotion/demotion

## Common Use Cases

### User Management Workflow
1. **Create Account:** Use create endpoint with required information
2. **Search Users:** Use get endpoint with search and filters
3. **Update Profile:** Use update endpoint for profile changes
4. **Change Password:** Use update endpoint with new password
5. **Deactivate Account:** Use delete endpoint for soft deletion
6. **Restore Account:** Use delete endpoint with restore flag

### Administrative Tasks
1. **User Audit:** Get all users with pagination
2. **Role Management:** Filter by role and update as needed
3. **Account Status:** Monitor and manage active/inactive users
4. **Bulk Operations:** Use search and filters for targeted actions

## cURL Examples

### Get All Users
```bash
curl -X GET "http://localhost/api/users/get.php?page=1&limit=10" \
  -H "Authorization: Bearer YOUR_ADMIN_TOKEN"
```

### Get Single User
```bash
curl -X GET "http://localhost/api/users/get.php?id=1" \
  -H "Authorization: Bearer YOUR_ADMIN_TOKEN"
```

### Search Users
```bash
curl -X GET "http://localhost/api/users/get.php?q=john&role=admin&is_active=1" \
  -H "Authorization: Bearer YOUR_ADMIN_TOKEN"
```

### Create User
```bash
curl -X POST "http://localhost/api/users/create.php" \
  -H "Content-Type: application/json" \
  -H "Authorization: Bearer YOUR_ADMIN_TOKEN" \
  -d '{
    "full_name": "John Doe",
    "email": "john.doe@example.com",
    "password": "SecurePassword123!",
    "phone": "+1234567890",
    "role": "employee"
  }'
```

### Update User
```bash
curl -X PUT "http://localhost/api/users/update.php?id=1" \
  -H "Content-Type: application/json" \
  -H "Authorization: Bearer YOUR_ADMIN_TOKEN" \
  -d '{
    "full_name": "John Updated Doe",
    "role": "admin"
  }'
```

### Change Password
```bash
curl -X PATCH "http://localhost/api/users/update.php" \
  -H "Content-Type: application/json" \
  -H "Authorization: Bearer YOUR_ADMIN_TOKEN" \
  -d '{
    "id": 1,
    "password": "NewSecurePassword123!"
  }'
```

### Deactivate User
```bash
curl -X DELETE "http://localhost/api/users/delete.php?id=1" \
  -H "Authorization: Bearer YOUR_ADMIN_TOKEN"
```

### Restore User
```bash
curl -X POST "http://localhost/api/users/delete.php" \
  -H "Content-Type: application/json" \
  -H "Authorization: Bearer YOUR_ADMIN_TOKEN" \
  -d '{
    "id": 1,
    "restore": 1
  }'
```

### Filter Active Employees
```bash
curl -X GET "http://localhost/api/users/get.php?role=employee&is_active=1&page=1&limit=20" \
  -H "Authorization: Bearer YOUR_ADMIN_TOKEN"
```

## Notes

1. **Admin Only:** All endpoints require admin authentication
2. **Email Immutability:** Email addresses cannot be changed after creation
3. **Soft Deletion:** Users are deactivated, not permanently deleted
4. **Password Security:** Passwords are automatically hashed using secure methods
5. **Unique Constraints:** Email and phone must be unique across all users
6. **Role Validation:** Only `admin` and `employee` roles are supported
7. **Phone Flexibility:** Phone numbers are optional and can be cleared
8. **Search Capability:** Full-text search across multiple fields
9. **Pagination:** All list endpoints support pagination
10. **Data Consistency:** Automatic timestamp management and validation