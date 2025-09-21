# Products API Documentation

## Overview
The Products API provides endpoints for managing products in the photography store system. Products belong to subcategories and can be filtered by various criteria including type, brand, price range, and search queries.

## Base URL
```
http://localhost/api/products/
```

## Database Schema
```sql
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
```

## Endpoints

### 1. GET Products
**Endpoint:** `GET /get.php`

#### Get Single Product by ID
```
GET /get.php?id=1
```

#### Get Single Product by Slug
```
GET /get.php?slug=canon-eos-r5
```

#### Get All Products (Paginated)
```
GET /get.php?page=1&limit=20
```

#### Query Parameters
- `id` (integer): Get specific product by ID
- `slug` (string): Get specific product by slug
- `page` (integer, default: 1): Page number for pagination
- `limit` (integer, default: 20, max: 100): Items per page
- `subcategory_id` (integer): Filter by subcategory
- `category_id` (integer): Filter by category
- `type` (enum): Filter by type (`videography`, `photography`, `both`)
- `brand` (string): Filter by brand name
- `is_active` (boolean): Filter by active status
- `is_featured` (boolean): Filter by featured status
- `min_price` (decimal): Minimum price filter
- `max_price` (decimal): Maximum price filter
- `q` (string): Search in name, model, SKU, or description

#### Response Format
```json
{
  "success": true,
  "data": [
    {
      "id": 1,
      "subcategory_id": 1,
      "category_id": 1,
      "name": "Canon EOS R5",
      "model": "EOS R5",
      "slug": "canon-eos-r5",
      "sku": "CAN-R5-001",
      "description": "Professional mirrorless camera",
      "short_description": "45MP full-frame mirrorless",
      "price": "3899.00",
      "discount_price": "3599.00",
      "type": "both",
      "brand": "Canon",
      "is_featured": 1,
      "is_active": 1,
      "meta_title": "Canon EOS R5 - Professional Camera",
      "meta_description": "High-resolution mirrorless camera",
      "primary_image_url": "http://localhost/uploads/products/canon-r5.jpg",
      "subcategory_name": "Mirrorless Cameras",
      "category_name": "Cameras",
      "active_discount": {...},
      "discounted_price": "3599.00",
      "discount_amount": 300.00,
      "discount_percentage": 7.69,
      "created_at": "2024-01-15 10:30:00",
      "updated_at": "2024-01-15 10:30:00"
    }
  ],
  "pagination": {
    "page": 1,
    "limit": 20,
    "total": 150,
    "pages": 8
  }
}
```

### 2. Search Products
**Endpoint:** `GET /search.php`

#### Basic Search
```
GET /search.php?q=canon
```

#### Advanced Search with Filters
```
GET /search.php?q=camera&type=photography&brand=Canon&min_price=1000&max_price=5000
```

#### Query Parameters
- `q` (string): Search query (searches in name, model, SKU, description)
- `type` (enum): Filter by type (`videography`, `photography`, `both`)
- `brand` (string): Filter by brand
- `min_price` (decimal): Minimum price
- `max_price` (decimal): Maximum price
- `page` (integer, default: 1): Page number
- `limit` (integer, default: 20, max: 100): Items per page

### 3. Create Product
**Endpoint:** `POST /create.php`
**Authentication:** Admin/Employee required

#### Request Body (JSON)
```json
{
  "subcategory_id": 1,
  "name": "Canon EOS R5",
  "model": "EOS R5",
  "slug": "canon-eos-r5",
  "sku": "CAN-R5-001",
  "description": "Professional mirrorless camera with 45MP sensor",
  "short_description": "45MP full-frame mirrorless camera",
  "price": 3899.00,
  "discount_price": 3599.00,
  "type": "both",
  "brand": "Canon",
  "is_featured": true,
  "is_active": true,
  "meta_title": "Canon EOS R5 - Professional Camera",
  "meta_description": "High-resolution mirrorless camera for professionals",
  "image_url": "https://example.com/canon-r5.jpg"
}
```

#### Request Body (Form Data with File Upload)
```
POST /create.php
Content-Type: multipart/form-data

subcategory_id=1
name=Canon EOS R5
model=EOS R5
type=both
price=3899.00
image=@/path/to/image.jpg
```

#### Required Fields
- `subcategory_id` (integer): Must exist in subcategories table
- `name` (string): Product name
- `type` (enum): Must be `videography`, `photography`, or `both`
- `price` (decimal): Product price

#### Optional Fields
- `model`, `slug`, `sku`, `description`, `short_description`
- `discount_price`, `brand`, `is_featured`, `is_active`
- `meta_title`, `meta_description`, `image_url`

### 4. Update Product
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
  "name": "Updated Canon EOS R5",
  "price": 3799.00,
  "is_featured": false
}
```

### 5. Delete Product
**Endpoint:** `POST|DELETE /delete.php`
**Authentication:** Admin/Employee required

#### Soft Delete (Default)
```json
{
  "id": 1
}
```

#### Restore Product
```json
{
  "id": 1,
  "restore": true
}
```

#### Hard Delete (Admin Only)
```json
{
  "id": 1,
  "hard": true
}
```

## Error Codes
- `400` - Bad Request (missing required fields, invalid data)
- `401` - Unauthorized (authentication required)
- `403` - Forbidden (insufficient permissions)
- `404` - Not Found (product doesn't exist)
- `405` - Method Not Allowed
- `409` - Conflict (duplicate slug/SKU)
- `413` - Payload Too Large (file upload)
- `422` - Unprocessable Entity (validation errors)
- `500` - Internal Server Error

## Authentication
Most endpoints require authentication via the `protect_admin_employee.php` middleware:
- **Create:** Admin or Employee
- **Update:** Admin or Employee  
- **Delete:** Admin or Employee (Hard delete: Admin only)
- **Get/Search:** Public access

## File Upload
- **Supported formats:** JPG, JPEG, PNG, WebP
- **Max file size:** 5MB
- **Upload directory:** `/uploads/products/`
- **URL format:** `http://localhost/uploads/products/filename.jpg`

## Features

### Automatic Slug Generation
If no slug is provided, it's automatically generated from the product name.

### Price Validation
- `discount_price` cannot be greater than `price`
- Prices are stored as DECIMAL(10,2)

### Search Functionality
- Full-text search in name, model, SKU, and description
- Boolean mode search with multiple terms
- Combines with filters (type, brand, price range)

### Discount System
Products support an integrated discount system that calculates:
- Active discounts from discount rules
- Final discounted price
- Discount amount and percentage

## cURL Examples

### Get All Products
```bash
curl -X GET "http://localhost/api/products/get.php?page=1&limit=10"
```

### Search Products
```bash
curl -X GET "http://localhost/api/products/search.php?q=canon&type=photography"
```

### Create Product
```bash
curl -X POST "http://localhost/api/products/create.php" \
  -H "Content-Type: application/json" \
  -d '{
    "subcategory_id": 1,
    "name": "Canon EOS R5",
    "type": "both",
    "price": 3899.00,
    "brand": "Canon"
  }'
```

### Upload Product with Image
```bash
curl -X POST "http://localhost/api/products/create.php" \
  -F "subcategory_id=1" \
  -F "name=Canon EOS R5" \
  -F "type=both" \
  -F "price=3899.00" \
  -F "image=@/path/to/image.jpg"
```

### Update Product
```bash
curl -X PUT "http://localhost/api/products/update.php?id=1" \
  -H "Content-Type: application/json" \
  -d '{"price": 3799.00, "is_featured": true}'
```

### Delete Product
```bash
curl -X DELETE "http://localhost/api/products/delete.php" \
  -H "Content-Type: application/json" \
  -d '{"id": 1}'
```

## Notes

1. **Type Field:** Must be one of `videography`, `photography`, or `both`
2. **Relationships:** Products belong to subcategories, which belong to categories
3. **Images:** Primary product image is fetched from `product_images` table
4. **Soft Delete:** Deleted products have `is_active=0`, hard delete removes from database
5. **Search:** Supports both simple text search and advanced filtering
6. **Pricing:** Supports regular price and optional discount price
7. **SEO:** Includes meta_title and meta_description fields for SEO optimization