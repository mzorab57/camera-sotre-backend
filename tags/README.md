# Tags API Documentation

## Overview
The Tags API provides endpoints for managing product tags in the photography store system. Tags are used to categorize and label products with keywords for better searchability and organization.

## Base URL
```
http://localhost/api/tags/
```

## Database Schema
```sql
CREATE TABLE IF NOT EXISTS tags (
  id INT PRIMARY KEY AUTO_INCREMENT,
  name VARCHAR(100) NOT NULL UNIQUE,
  slug VARCHAR(100) UNIQUE NOT NULL,
  created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
  INDEX idx_tags_name (name)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;
```

## Endpoints

### 1. GET Tags
**Endpoint:** `GET /get.php`

#### Get Single Tag by ID
```
GET /get.php?id=1
```

#### Get Single Tag by Slug
```
GET /get.php?slug=professional-camera
```

#### Get Tags for a Product
```
GET /get.php?product_id=1
```

#### Get All Tags (Paginated)
```
GET /get.php?page=1&limit=20
```

#### Search Tags
```
GET /get.php?q=camera&page=1&limit=10
```

#### Query Parameters
- `id` (integer): Get specific tag by ID
- `slug` (string): Get specific tag by slug
- `product_id` (integer): Get all tags associated with a product
- `page` (integer, default: 1): Page number for pagination
- `limit` (integer, default: 20, max: 100): Number of items per page
- `q` (string): Search query (searches in name and slug)

#### Response Format (Single Tag)
```json
{
  "success": true,
  "data": {
    "id": 1,
    "name": "Professional Camera",
    "slug": "professional-camera",
    "created_at": "2024-01-15 10:30:00"
  }
}
```

#### Response Format (Multiple Tags)
```json
{
  "success": true,
  "data": [
    {
      "id": 1,
      "name": "Professional Camera",
      "slug": "professional-camera",
      "created_at": "2024-01-15 10:30:00"
    },
    {
      "id": 2,
      "name": "Full Frame",
      "slug": "full-frame",
      "created_at": "2024-01-15 10:31:00"
    }
  ],
  "pagination": {
    "page": 1,
    "limit": 20,
    "total": 45,
    "pages": 3
  }
}
```

### 2. Create Tag
**Endpoint:** `POST /create.php`
**Authentication:** Admin/Employee required

#### Request Format (JSON)
```json
{
  "name": "Professional Camera",
  "slug": "professional-camera"
}
```

#### Request Format (Form Data)
```
POST /create.php
Content-Type: application/x-www-form-urlencoded

name=Professional Camera&slug=professional-camera
```

#### Request Parameters
- `name` (string, required): Tag name (must be unique)
- `slug` (string, optional): URL-friendly slug (auto-generated from name if not provided)

#### Response Format
```json
{
  "success": true,
  "data": {
    "id": 1,
    "name": "Professional Camera",
    "slug": "professional-camera",
    "created_at": "2024-01-15 10:30:00"
  }
}
```

### 3. Update Tag
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
  "name": "Updated Professional Camera",
  "slug": "updated-professional-camera"
}
```

#### Updatable Fields
- `name` (string): Update tag name (must be unique)
- `slug` (string): Update slug (auto-generated from name if empty)

#### Response Format
```json
{
  "success": true,
  "data": {
    "id": 1,
    "name": "Updated Professional Camera",
    "slug": "updated-professional-camera",
    "created_at": "2024-01-15 10:30:00"
  }
}
```

### 4. Delete Tag
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

#### Response Format
```json
{
  "success": true,
  "message": "Tag deleted"
}
```

## Error Codes
- `400` - Bad Request (missing required fields, no fields to update)
- `401` - Unauthorized (authentication required)
- `403` - Forbidden (insufficient permissions)
- `404` - Not Found (tag doesn't exist)
- `405` - Method Not Allowed
- `409` - Conflict (duplicate name or slug)
- `422` - Unprocessable Entity (validation errors, empty name)
- `500` - Internal Server Error (database errors)

## Authentication
All modification endpoints require authentication via the `protect_admin_employee.php` middleware:
- **Create:** Admin or Employee
- **Update:** Admin or Employee
- **Delete:** Admin or Employee
- **Get:** Public access

## Features

### Automatic Slug Generation
- If no slug is provided during creation, it's automatically generated from the name
- Slug generation converts text to lowercase, replaces spaces with hyphens, and removes special characters
- Example: "Professional Camera" → "professional-camera"

### Search Functionality
- Search works across both name and slug fields
- Uses LIKE queries with wildcards for partial matching
- Case-insensitive search

### Pagination
- Default page size: 20 items
- Maximum page size: 100 items
- Returns pagination metadata including total count and page numbers

### Product Association
- Tags can be retrieved for specific products via `product_id` parameter
- Results are ordered by tag name for consistent display
- Uses JOIN with `product_tags` table for efficient querying

### Cascading Delete
- When a tag is deleted, all associated product-tag relationships are automatically removed
- Uses foreign key constraints with CASCADE for data integrity

## Common Tag Examples

### Camera Tags
- "Professional", "Entry Level", "Mirrorless", "DSLR"
- "Full Frame", "APS-C", "Micro Four Thirds"
- "Weather Sealed", "Compact", "Lightweight"

### Lens Tags
- "Prime", "Zoom", "Telephoto", "Wide Angle"
- "Image Stabilization", "Fast Aperture", "Macro"
- "Portrait", "Landscape", "Street Photography"

### Accessory Tags
- "Essential", "Professional", "Budget Friendly"
- "Travel", "Studio", "Outdoor"
- "Wireless", "Waterproof", "Compact"

## cURL Examples

### Get All Tags
```bash
curl -X GET "http://localhost/api/tags/get.php?page=1&limit=10"
```

### Search Tags
```bash
curl -X GET "http://localhost/api/tags/get.php?q=camera"
```

### Get Tag by Slug
```bash
curl -X GET "http://localhost/api/tags/get.php?slug=professional-camera"
```

### Get Tags for Product
```bash
curl -X GET "http://localhost/api/tags/get.php?product_id=1"
```

### Create Tag
```bash
curl -X POST "http://localhost/api/tags/create.php" \
  -H "Content-Type: application/json" \
  -d '{
    "name": "Professional Camera",
    "slug": "professional-camera"
  }'
```

### Create Tag (Auto-generate Slug)
```bash
curl -X POST "http://localhost/api/tags/create.php" \
  -H "Content-Type: application/json" \
  -d '{
    "name": "Full Frame Sensor"
  }'
```

### Update Tag
```bash
curl -X PUT "http://localhost/api/tags/update.php?id=1" \
  -H "Content-Type: application/json" \
  -d '{
    "name": "Updated Professional Camera"
  }'
```

### Delete Tag
```bash
curl -X DELETE "http://localhost/api/tags/delete.php?id=1"
```

## Notes

1. **Unique Constraints:** Both name and slug must be unique across all tags
2. **Slug Auto-generation:** Empty slugs are automatically generated from the tag name
3. **Case Sensitivity:** Tag names are case-sensitive, but slugs are always lowercase
4. **Product Integration:** Tags integrate seamlessly with the product_tags junction table
5. **Search Performance:** Indexed on name field for fast search operations
6. **Data Integrity:** Foreign key constraints ensure referential integrity
7. **Flexible Updates:** Partial updates supported - only specified fields are modified
8. **Cascading Operations:** Deleting a tag automatically removes all product associations