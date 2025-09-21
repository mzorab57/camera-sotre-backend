# Product Images API Documentation

## Overview
The Product Images API provides endpoints for managing product images in the photography store system. Each product can have multiple images with one designated as primary. Images support display ordering and can be uploaded as files or referenced by URL.

## Base URL
```
http://localhost/api/product_images/
```

## Database Schema
```sql
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
```

## Endpoints

### 1. GET Product Images
**Endpoint:** `GET /get.php`

#### Get Single Image by ID
```
GET /get.php?id=1
```

#### Get All Images for a Product
```
GET /get.php?product_id=1
```

#### Query Parameters
- `id` (integer): Get specific image by ID
- `product_id` (integer, required if no id): Get all images for a product

#### Response Format
```json
{
  "success": true,
  "data": [
    {
      "id": 1,
      "product_id": 1,
      "image_url": "/uploads/products/20240115103000-a1b2c3d4e5f6.jpg",
      "image_full_url": "http://localhost/uploads/products/20240115103000-a1b2c3d4e5f6.jpg",
      "is_primary": 1,
      "display_order": 0,
      "created_at": "2024-01-15 10:30:00",
      "updated_at": "2024-01-15 10:30:00"
    },
    {
      "id": 2,
      "product_id": 1,
      "image_url": "https://example.com/external-image.jpg",
      "is_primary": 0,
      "display_order": 10,
      "created_at": "2024-01-15 10:35:00",
      "updated_at": "2024-01-15 10:35:00"
    }
  ]
}
```

### 2. Create Product Images
**Endpoint:** `POST /create.php`
**Authentication:** Admin/Employee required

#### Upload Single Image File
```
POST /create.php
Content-Type: multipart/form-data

product_id=1
image=@/path/to/image.jpg
start_order=0
```

#### Upload Multiple Image Files
```
POST /create.php
Content-Type: multipart/form-data

product_id=1
images[]=@/path/to/image1.jpg
images[]=@/path/to/image2.jpg
primary_index=0
start_order=0
```

#### Add Image by URL (JSON)
```json
{
  "product_id": 1,
  "image_url": "https://example.com/product-image.jpg",
  "start_order": 0
}
```

#### Add Multiple Images by URL (JSON)
```json
{
  "product_id": 1,
  "images": [
    {
      "image_url": "https://example.com/image1.jpg",
      "is_primary": true,
      "display_order": 0
    },
    {
      "image_url": "https://example.com/image2.jpg",
      "is_primary": false,
      "display_order": 10
    }
  ]
}
```

#### Request Parameters

**Form Data (File Upload):**
- `product_id` (integer, required): Product ID that must exist
- `image` (file): Single image file
- `images[]` (files): Multiple image files
- `primary_index` (integer): Index of file to set as primary (0-based)
- `start_order` (integer): Starting display order (auto-increments by 10)
- `image_url` (string): External image URL (if no files)

**JSON Data:**
- `product_id` (integer, required): Product ID that must exist
- `image_url` (string): Single external image URL
- `images` (array): Array of image objects with `image_url`, `is_primary`, `display_order`
- `start_order` (integer): Starting display order for batch uploads

#### Required Fields
- `product_id`: Must exist in products table
- Either file upload OR `image_url` OR `images` array

### 3. Update Product Image
**Endpoint:** `POST|PUT|PATCH /update.php`
**Authentication:** Admin/Employee required

#### Update by ID (Query Parameter)
```
PUT /update.php?id=1
```

#### Update by ID (Request Body)
```json
{
  "id": 1,
  "display_order": 20,
  "is_primary": true
}
```

#### Updatable Fields
- `display_order` (integer): Change display order
- `is_primary` (boolean): Set as primary image (automatically unsets others)

#### Primary Image Logic
- Setting `is_primary: true` automatically sets all other images for the product to `is_primary: false`
- Setting `is_primary: false` removes primary status, but ensures at least one image remains primary
- If no primary image exists after update, the first image (by display_order, then id) becomes primary

### 4. Delete Product Image
**Endpoint:** `POST|DELETE /delete.php`
**Authentication:** Admin/Employee required

#### Delete by ID (Query Parameter)
```
DELETE /delete.php?id=1
```

#### Delete by ID (Request Body)
```json
{
  "id": 1
}
```

#### Delete Behavior
- Permanently deletes the image record from database
- Deletes local image file if stored in `/uploads/products/`
- If deleted image was primary, automatically promotes next image to primary
- Returns remaining images for the product after deletion

## Error Codes
- `400` - Bad Request (missing required fields, no files/URLs provided)
- `401` - Unauthorized (authentication required)
- `403` - Forbidden (insufficient permissions)
- `404` - Not Found (image or product doesn't exist)
- `405` - Method Not Allowed
- `413` - Payload Too Large (file size > 5MB)
- `422` - Unprocessable Entity (invalid file type, invalid URL format, product not found)
- `500` - Internal Server Error (file upload/move failed)

## Authentication
All modification endpoints require authentication via the `protect_admin_employee.php` middleware:
- **Create:** Admin or Employee
- **Update:** Admin or Employee
- **Delete:** Admin or Employee
- **Get:** Public access

## File Upload Specifications

### Supported Formats
- **JPEG** (.jpg, .jpeg)
- **PNG** (.png)
- **WebP** (.webp)
- **GIF** (.gif)

### File Constraints
- **Max file size:** 5MB per file
- **Upload directory:** `/uploads/products/`
- **File naming:** `YmdHis-{random}.{ext}` (e.g., `20240115103000-a1b2c3d4e5f6.jpg`)
- **Permissions:** Files are set to 644 after upload

### URL Format
- **Relative:** `/uploads/products/filename.jpg`
- **Full URL:** `http://localhost/uploads/products/filename.jpg`
- **External URLs:** Must start with `http://` or `https://`

## Display Order System

### Automatic Ordering
- If no `start_order` specified, uses `MAX(display_order) + 10`
- Multiple uploads increment by 10 for each subsequent image
- Default display order starts at 0

### Sorting Logic
Images are returned in this order:
1. Primary images first (`is_primary DESC`)
2. Then by display order (`display_order ASC`)
3. Finally by ID (`id ASC`)

## Primary Image Management

### Rules
- Each product should have exactly one primary image
- Setting an image as primary automatically unsets others
- Deleting primary image promotes the next image (by sort order)
- If no images remain, no primary image exists

### Automatic Promotion
When primary image is deleted or unset:
1. System checks if any primary image exists
2. If none, selects first image by `display_order ASC, id ASC`
3. Automatically sets that image as primary

## cURL Examples

### Get All Images for Product
```bash
curl -X GET "http://localhost/api/product_images/get.php?product_id=1"
```

### Upload Single Image
```bash
curl -X POST "http://localhost/api/product_images/create.php" \
  -F "product_id=1" \
  -F "image=@/path/to/image.jpg" \
  -F "start_order=0"
```

### Upload Multiple Images
```bash
curl -X POST "http://localhost/api/product_images/create.php" \
  -F "product_id=1" \
  -F "images[]=@/path/to/image1.jpg" \
  -F "images[]=@/path/to/image2.jpg" \
  -F "primary_index=0"
```

### Add Image by URL
```bash
curl -X POST "http://localhost/api/product_images/create.php" \
  -H "Content-Type: application/json" \
  -d '{
    "product_id": 1,
    "image_url": "https://example.com/product-image.jpg"
  }'
```

### Add Multiple Images by URL
```bash
curl -X POST "http://localhost/api/product_images/create.php" \
  -H "Content-Type: application/json" \
  -d '{
    "product_id": 1,
    "images": [
      {
        "image_url": "https://example.com/image1.jpg",
        "is_primary": true,
        "display_order": 0
      },
      {
        "image_url": "https://example.com/image2.jpg",
        "display_order": 10
      }
    ]
  }'
```

### Update Image Order and Primary Status
```bash
curl -X PUT "http://localhost/api/product_images/update.php?id=1" \
  -H "Content-Type: application/json" \
  -d '{
    "display_order": 20,
    "is_primary": true
  }'
```

### Delete Image
```bash
curl -X DELETE "http://localhost/api/product_images/delete.php?id=1"
```

## Notes

1. **Product Validation:** All operations verify that the `product_id` exists in the products table
2. **File Management:** Local files in `/uploads/products/` are automatically deleted when image records are removed
3. **Primary Image Logic:** System ensures each product has at most one primary image and handles automatic promotion
4. **Display Order:** Images can be reordered using the `display_order` field with automatic gap management
5. **Mixed Sources:** Products can have both uploaded files and external URL images
6. **Batch Operations:** Multiple images can be uploaded in a single request with automatic ordering
7. **URL Generation:** System automatically generates full URLs for relative image paths
8. **Error Handling:** Comprehensive validation for file types, sizes, and URL formats