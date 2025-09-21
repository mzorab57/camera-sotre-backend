# Categories API Documentation

## Overview
This API provides CRUD operations for managing product categories in the photography store system.

## Base URL
```
http://localhost/api/categories
```

## Database Schema
```sql
CREATE TABLE IF NOT EXISTS categories (
  id INT PRIMARY KEY AUTO_INCREMENT,
  name VARCHAR(255) NOT NULL,
  slug VARCHAR(255) UNIQUE NOT NULL,
  image_url VARCHAR(500),
  is_active BOOLEAN DEFAULT TRUE,
  created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
  updated_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;
```

## Endpoints

### 1. Get Categories
**GET** `/api/categories/get.php`

#### Query Parameters
- `id` (integer, optional) - Get specific category by ID
- `slug` (string, optional) - Get specific category by slug
- `page` (integer, optional, default: 1) - Page number for pagination
- `limit` (integer, optional, default: 20, max: 100) - Items per page
- `q` (string, optional) - Search term (searches in name and slug)
- `is_active` (boolean, optional) - Filter by active status (1/true or 0/false)

#### Response Examples

**Get all categories (paginated):**
```json
{
  "success": true,
  "data": [
    {
      "id": 1,
      "name": "Cameras",
      "slug": "cameras",
      "image_url": "/uploads/categories/20240115123456-abc123.jpg",
      "is_active": 1,
      "created_at": "2024-01-15 12:34:56",
      "updated_at": "2024-01-15 12:34:56"
    }
  ],
  "pagination": {
    "page": 1,
    "limit": 20,
    "total": 5,
    "pages": 1,
    "has_next": false,
    "has_prev": false
  }
}
```

**Get single category:**
```json
{
  "success": true,
  "data": {
    "id": 1,
    "name": "Cameras",
    "slug": "cameras",
    "image_url": "/uploads/categories/20240115123456-abc123.jpg",
    "is_active": 1,
    "created_at": "2024-01-15 12:34:56",
    "updated_at": "2024-01-15 12:34:56"
  }
}
```

### 2. Create Category
**POST** `/api/categories/create.php`

#### Content Types Supported
- `application/json` - For JSON data
- `multipart/form-data` - For file uploads

#### Request Body (JSON)
```json
{
  "name": "Lenses",
  "slug": "lenses",
  "image_url": "https://example.com/image.jpg",
  "is_active": true
}
```

#### Request Body (Form Data)
```
name: "Lenses"
slug: "lenses" (optional, auto-generated from name if empty)
image_url: "https://example.com/image.jpg" (optional)
is_active: true (optional, default: true)
image: [file] (optional, for file upload)
```

#### File Upload Requirements
- **Allowed formats:** JPG, PNG, WEBP, GIF
- **Maximum size:** 5MB
- **Upload directory:** `/uploads/categories/`

#### Response (Success - 201)
```json
{
  "success": true,
  "data": {
    "id": 2,
    "name": "Lenses",
    "slug": "lenses",
    "image_url": "/uploads/categories/20240115123456-def789.jpg",
    "is_active": 1,
    "created_at": "2024-01-15 12:34:56",
    "updated_at": "2024-01-15 12:34:56"
  },
  "image_full_url": "http://localhost/uploads/categories/20240115123456-def789.jpg"
}
```

### 3. Update Category
**POST/PUT/PATCH** `/api/categories/update.php?id={id}`

#### Request Body
```json
{
  "name": "Updated Category Name",
  "slug": "updated-slug",
  "image_url": "https://example.com/new-image.jpg",
  "is_active": false
}
```

#### Response (Success - 200)
```json
{
  "success": true,
  "data": {
    "id": 1,
    "name": "Updated Category Name",
    "slug": "updated-slug",
    "image_url": "https://example.com/new-image.jpg",
    "is_active": 0,
    "created_at": "2024-01-15 12:34:56",
    "updated_at": "2024-01-15 13:45:00"
  }
}
```

### 4. Delete Category
**POST/DELETE** `/api/categories/delete.php?id={id}`

#### Query Parameters
- `id` (integer, required) - Category ID to delete
- `restore` (integer, optional) - Set to 1 to restore (set is_active=1)
- `hard` (integer, optional) - Set to 1 for permanent deletion (admin only)

#### Request Body (Optional)
```json
{
  "restore": 0,
  "hard": 0
}
```

#### Response Examples

**Soft delete (deactivate):**
```json
{
  "success": true,
  "message": "Category deactivated (is_active=0)",
  "data": {
    "id": 1,
    "name": "Cameras",
    "slug": "cameras",
    "image_url": "/uploads/categories/image.jpg",
    "is_active": 0,
    "created_at": "2024-01-15 12:34:56",
    "updated_at": "2024-01-15 14:00:00"
  }
}
```

**Restore:**
```json
{
  "success": true,
  "message": "Category restored (is_active=1)",
  "data": {
    "id": 1,
    "name": "Cameras",
    "slug": "cameras",
    "image_url": "/uploads/categories/image.jpg",
    "is_active": 1,
    "created_at": "2024-01-15 12:34:56",
    "updated_at": "2024-01-15 14:05:00"
  }
}
```

## Error Responses

### Common Error Codes
- **400 Bad Request** - Missing required fields or invalid data
- **404 Not Found** - Category not found
- **405 Method Not Allowed** - Invalid HTTP method
- **409 Conflict** - Duplicate slug
- **413 Payload Too Large** - File too large (>5MB)
- **422 Unprocessable Entity** - Invalid file type or URL format
- **500 Internal Server Error** - Database or server error

### Error Response Format
```json
{
  "error": "Error message description",
  "details": "Additional error details (optional)"
}
```

## Authentication
Most endpoints require authentication:
- **GET** endpoints are public
- **POST/PUT/PATCH/DELETE** endpoints require admin or employee authentication
- **Hard delete** requires admin authentication only

## Usage Examples

### cURL Examples

**Get all categories:**
```bash
curl -X GET "http://localhost/api/categories/get.php"
```

**Search categories:**
```bash
curl -X GET "http://localhost/api/categories/get.php?q=camera&is_active=1"
```

**Create category with JSON:**
```bash
curl -X POST "http://localhost/api/categories/create.php" \
  -H "Content-Type: application/json" \
  -H "Authorization: Bearer YOUR_TOKEN" \
  -d '{
    "name": "Tripods",
    "image_url": "https://example.com/tripod.jpg"
  }'
```

**Upload category with image:**
```bash
curl -X POST "http://localhost/api/categories/create.php" \
  -H "Authorization: Bearer YOUR_TOKEN" \
  -F "name=Lighting" \
  -F "image=@/path/to/image.jpg"
```

**Update category:**
```bash
curl -X PUT "http://localhost/api/categories/update.php?id=1" \
  -H "Content-Type: application/json" \
  -H "Authorization: Bearer YOUR_TOKEN" \
  -d '{
    "name": "Updated Category",
    "is_active": false
  }'
```

**Delete category (soft):**
```bash
curl -X DELETE "http://localhost/api/categories/delete.php?id=1" \
  -H "Authorization: Bearer YOUR_TOKEN"
```

## Notes
- Slugs are automatically generated from the name if not provided
- Image uploads are stored in `/uploads/categories/` directory
- Categories support both local file uploads and external image URLs
- Soft delete is the default behavior (sets is_active=0)
- Hard delete permanently removes the category and cascades to subcategories
- All timestamps are in MySQL TIMESTAMP format
- The API supports both Kurdish and English content