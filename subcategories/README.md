# Subcategories API Documentation

## Overview
This API provides CRUD operations for managing product subcategories in the photography store system. Subcategories belong to categories and can be filtered by type (photography, videography, or both).

## Base URL
```
http://localhost/api/subcategories
```

## Database Schema
```sql
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
```

## Endpoints

### 1. Get Subcategories
**GET** `/api/subcategories/get.php`

#### Query Parameters
- `id` (integer, optional) - Get specific subcategory by ID
- `slug` (string, optional) - Get specific subcategory by slug
- `category_id` (integer, optional) - Filter by parent category ID
- `type` (string, optional) - Filter by type: 'photography', 'videography', or 'both'
- `page` (integer, optional, default: 1) - Page number for pagination
- `limit` (integer, optional, default: 20, max: 100) - Items per page
- `q` (string, optional) - Search term (searches in name and slug)
- `is_active` (boolean, optional) - Filter by active status (1/true or 0/false)

#### Response Examples

**Get all subcategories (paginated):**
```json
{
  "success": true,
  "data": [
    {
      "id": 1,
      "category_id": 1,
      "name": "DSLR Cameras",
      "slug": "dslr-cameras",
      "type": "photography",
      "image_url": "/uploads/subcategories/20240115123456-abc123.jpg",
      "is_active": 1,
      "created_at": "2024-01-15 12:34:56",
      "updated_at": "2024-01-15 12:34:56",
      "category_name": "Cameras"
    }
  ],
  "pagination": {
    "page": 1,
    "limit": 20,
    "total": 15,
    "pages": 1
  }
}
```

**Get single subcategory:**
```json
{
  "success": true,
  "data": {
    "id": 1,
    "category_id": 1,
    "name": "DSLR Cameras",
    "slug": "dslr-cameras",
    "type": "photography",
    "image_url": "/uploads/subcategories/20240115123456-abc123.jpg",
    "is_active": 1,
    "created_at": "2024-01-15 12:34:56",
    "updated_at": "2024-01-15 12:34:56"
  }
}
```

### 2. Get Subcategories with Products
**GET** `/api/subcategories/with_products.php`

Returns subcategories along with their associated products count and details.

### 3. Create Subcategory
**POST** `/api/subcategories/create.php`

#### Content Types Supported
- `application/json` - For JSON data
- `multipart/form-data` - For file uploads

#### Request Body (JSON)
```json
{
  "category_id": 1,
  "name": "Mirrorless Cameras",
  "slug": "mirrorless-cameras",
  "type": "photography",
  "image_url": "https://example.com/image.jpg",
  "is_active": true
}
```

#### Request Body (Form Data)
```
category_id: 1 (required)
name: "Mirrorless Cameras" (required)
type: "photography" (required - must be: photography, videography, or both)
slug: "mirrorless-cameras" (optional, auto-generated from name if empty)
image_url: "https://example.com/image.jpg" (optional)
is_active: true (optional, default: true)
image: [file] (optional, for file upload)
```

#### Required Fields
- `category_id` (integer) - Must be a valid category ID
- `name` (string) - Subcategory name
- `type` (string) - Must be one of: 'photography', 'videography', 'both'

#### File Upload Requirements
- **Allowed formats:** JPG, PNG, WEBP, GIF
- **Maximum size:** 5MB
- **Upload directory:** `/uploads/subcategories/`

#### Response (Success - 201)
```json
{
  "success": true,
  "data": {
    "id": 2,
    "category_id": 1,
    "name": "Mirrorless Cameras",
    "slug": "mirrorless-cameras",
    "type": "photography",
    "image_url": "/uploads/subcategories/20240115123456-def789.jpg",
    "is_active": 1,
    "created_at": "2024-01-15 12:34:56",
    "updated_at": "2024-01-15 12:34:56"
  },
  "image_full_url": "http://localhost/uploads/subcategories/20240115123456-def789.jpg"
}
```

### 4. Update Subcategory
**POST/PUT/PATCH** `/api/subcategories/update.php?id={id}`

#### Request Body
```json
{
  "category_id": 2,
  "name": "Updated Subcategory Name",
  "slug": "updated-slug",
  "type": "both",
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
    "category_id": 2,
    "name": "Updated Subcategory Name",
    "slug": "updated-slug",
    "type": "both",
    "image_url": "https://example.com/new-image.jpg",
    "is_active": 0,
    "created_at": "2024-01-15 12:34:56",
    "updated_at": "2024-01-15 13:45:00"
  }
}
```

### 5. Delete Subcategory
**POST/DELETE** `/api/subcategories/delete.php?id={id}`

#### Query Parameters
- `id` (integer, required) - Subcategory ID to delete
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
  "message": "Subcategory deactivated (is_active=0)",
  "data": {
    "id": 1,
    "category_id": 1,
    "name": "DSLR Cameras",
    "slug": "dslr-cameras",
    "type": "photography",
    "image_url": "/uploads/subcategories/image.jpg",
    "is_active": 0,
    "created_at": "2024-01-15 12:34:56",
    "updated_at": "2024-01-15 14:00:00"
  }
}
```

## Error Responses

### Common Error Codes
- **400 Bad Request** - Missing required fields (category_id, name, type)
- **404 Not Found** - Subcategory not found
- **405 Method Not Allowed** - Invalid HTTP method
- **409 Conflict** - Duplicate slug
- **413 Payload Too Large** - File too large (>5MB)
- **422 Unprocessable Entity** - Invalid type, category_id not found, or invalid file format
- **500 Internal Server Error** - Database or server error

### Error Response Format
```json
{
  "error": "Error message description",
  "details": "Additional error details (optional)"
}
```

## Type Values
The `type` field must be one of:
- `"photography"` - For photography-related subcategories
- `"videography"` - For videography-related subcategories  
- `"both"` - For subcategories that apply to both photography and videography

## Authentication
Most endpoints require authentication:
- **GET** endpoints are public
- **POST/PUT/PATCH/DELETE** endpoints require admin or employee authentication
- **Hard delete** requires admin authentication only

## Usage Examples

### cURL Examples

**Get all subcategories:**
```bash
curl -X GET "http://localhost/api/subcategories/get.php"
```

**Get subcategories by category:**
```bash
curl -X GET "http://localhost/api/subcategories/get.php?category_id=1&type=photography"
```

**Search subcategories:**
```bash
curl -X GET "http://localhost/api/subcategories/get.php?q=camera&is_active=1"
```

**Create subcategory with JSON:**
```bash
curl -X POST "http://localhost/api/subcategories/create.php" \
  -H "Content-Type: application/json" \
  -H "Authorization: Bearer YOUR_TOKEN" \
  -d '{
    "category_id": 1,
    "name": "Action Cameras",
    "type": "both",
    "image_url": "https://example.com/action-camera.jpg"
  }'
```

**Upload subcategory with image:**
```bash
curl -X POST "http://localhost/api/subcategories/create.php" \
  -H "Authorization: Bearer YOUR_TOKEN" \
  -F "category_id=1" \
  -F "name=Studio Lighting" \
  -F "type=photography" \
  -F "image=@/path/to/image.jpg"
```

**Update subcategory:**
```bash
curl -X PUT "http://localhost/api/subcategories/update.php?id=1" \
  -H "Content-Type: application/json" \
  -H "Authorization: Bearer YOUR_TOKEN" \
  -d '{
    "name": "Updated Subcategory",
    "type": "both",
    "is_active": false
  }'
```

**Delete subcategory (soft):**
```bash
curl -X DELETE "http://localhost/api/subcategories/delete.php?id=1" \
  -H "Authorization: Bearer YOUR_TOKEN"
```

## Notes
- Subcategories must belong to a valid category (foreign key constraint)
- Slugs are automatically generated from the name if not provided
- Image uploads are stored in `/uploads/subcategories/` directory
- Subcategories support both local file uploads and external image URLs
- The `type` field helps categorize products for photography vs videography use cases
- Soft delete is the default behavior (sets is_active=0)
- Hard delete permanently removes the subcategory and may cascade to related products
- All timestamps are in MySQL TIMESTAMP format
- The API supports both Kurdish and English content