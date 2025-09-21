# Product Tags API Documentation

## Overview
The Product Tags API manages the many-to-many relationship between products and tags in the photography store system. This junction table API allows you to associate products with multiple tags and retrieve products by their tags.

## Base URL
```
http://localhost/api/product_tags/
```

## Database Schema
```sql
CREATE TABLE IF NOT EXISTS product_tags (
  product_id INT NOT NULL,
  tag_id INT NOT NULL,
  PRIMARY KEY (product_id, tag_id),
  FOREIGN KEY (product_id) REFERENCES products(id) ON DELETE CASCADE,
  FOREIGN KEY (tag_id) REFERENCES tags(id) ON DELETE CASCADE,
  INDEX idx_product_tags_tag (tag_id)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;
```

## Endpoints

### 1. GET Product Tags
**Endpoint:** `GET /get.php`

#### Get All Product-Tag Relationships (Admin View)
```
GET /get.php?get_all=1&page=1&limit=20
```

#### Get Tags for a Product
```
GET /get.php?product_id=1
```

#### Get Products by Tag ID
```
GET /get.php?tag_id=5&page=1&limit=10
```

#### Get Products by Tag Slug
```
GET /get.php?tag_slug=professional-camera&page=1&limit=10
```

#### Query Parameters
- `get_all` (boolean): Get all product-tag relationships with pagination
- `product_id` (integer): Get all tags associated with a product
- `tag_id` (integer): Get all products associated with a tag
- `tag_slug` (string): Get all products associated with a tag by slug
- `page` (integer, default: 1): Page number for pagination
- `limit` (integer, default: 20, max: 100): Number of items per page

#### Response Format (Tags for Product)
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
  ]
}
```

#### Response Format (Products by Tag)
```json
{
  "success": true,
  "data": [
    {
      "id": 1,
      "name": "Canon EOS R5",
      "price": 3899.99,
      "brand_id": 1,
      "category_id": 1,
      "subcategory_id": 1,
      "is_active": 1,
      "primary_image_url": "http://localhost/uploads/products/canon-eos-r5-1.jpg",
      "created_at": "2024-01-15 09:00:00",
      "updated_at": "2024-01-15 09:00:00"
    }
  ],
  "pagination": {
    "page": 1,
    "limit": 10,
    "total": 25,
    "pages": 3
  }
}
```

#### Response Format (All Relationships)
```json
{
  "success": true,
  "data": [
    {
      "product_id": 1,
      "tag_id": 5,
      "product_name": "Canon EOS R5",
      "product_price": 3899.99,
      "tag_name": "Professional Camera",
      "tag_slug": "professional-camera"
    },
    {
      "product_id": 1,
      "tag_id": 8,
      "product_name": "Canon EOS R5",
      "product_price": 3899.99,
      "tag_name": "Full Frame",
      "tag_slug": "full-frame"
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

### 2. Create Product Tags
**Endpoint:** `POST /create.php`
**Authentication:** Admin/Employee required

#### Add Tags to Product by Tag IDs
```json
{
  "product_id": 1,
  "tag_ids": [5, 8, 12, 15]
}
```

#### Add Tags to Product by Slugs
```json
{
  "product_id": 1,
  "slugs": ["professional-camera", "full-frame", "weather-sealed"]
}
```

#### Add Tags with Auto-Creation
```json
{
  "product_id": 1,
  "slugs": ["new-tag", "another-tag"],
  "auto_create": true
}
```

#### Mixed Tag Assignment
```json
{
  "product_id": 1,
  "tag_ids": [5, 8],
  "slugs": ["professional-camera", "weather-sealed"],
  "auto_create": true
}
```

#### Request Parameters
- `product_id` (integer, required): Product ID that must exist
- `tag_ids` (array, optional): Array of existing tag IDs
- `slugs` (array, optional): Array of tag slugs
- `auto_create` (boolean, optional): Create tags that don't exist when using slugs

**Note:** You must provide either `tag_ids` or `slugs` (or both)

#### Response Format
```json
{
  "success": true,
  "data": [
    {
      "id": 5,
      "name": "Professional Camera",
      "slug": "professional-camera",
      "created_at": "2024-01-15 10:30:00"
    },
    {
      "id": 8,
      "name": "Full Frame",
      "slug": "full-frame",
      "created_at": "2024-01-15 10:31:00"
    }
  ]
}
```

### 3. Update Product Tags (Replace All)
**Endpoint:** `POST|PUT|PATCH /update.php`
**Authentication:** Admin/Employee required

#### Replace All Tags for Product
```json
{
  "product_id": 1,
  "tag_ids": [5, 8, 12]
}
```

#### Replace with Slugs
```json
{
  "product_id": 1,
  "slugs": ["professional-camera", "full-frame"],
  "auto_create": true
}
```

#### Clear All Tags
```json
{
  "product_id": 1,
  "tag_ids": []
}
```

#### Request Parameters
- `product_id` (integer, required): Product ID that must exist
- `tag_ids` (array, optional): Array of tag IDs to replace current tags
- `slugs` (array, optional): Array of tag slugs to replace current tags
- `auto_create` (boolean, optional): Create tags that don't exist when using slugs

**Note:** This endpoint replaces ALL existing tags for the product

### 4. Delete Product Tags
**Endpoint:** `POST|DELETE /delete.php`
**Authentication:** Admin/Employee required

#### Remove Specific Tags by IDs
```json
{
  "product_id": 1,
  "tag_ids": [5, 8]
}
```

#### Remove Specific Tags by Slugs
```json
{
  "product_id": 1,
  "slugs": ["professional-camera", "full-frame"]
}
```

#### Remove All Tags from Product
```json
{
  "product_id": 1,
  "all": true
}
```

#### Query Parameter Alternative
```
DELETE /delete.php?product_id=1&tag_ids[]=5&tag_ids[]=8
```

#### Request Parameters
- `product_id` (integer, required): Product ID that must exist
- `tag_ids` (array, optional): Array of tag IDs to remove
- `slugs` (array, optional): Array of tag slugs to remove
- `all` (boolean, optional): Remove all tags from the product

#### Response Format
```json
{
  "success": true,
  "data": [
    {
      "id": 12,
      "name": "Weather Sealed",
      "slug": "weather-sealed",
      "created_at": "2024-01-15 10:32:00"
    }
  ]
}
```

## Error Codes
- `400` - Bad Request (missing required fields, invalid parameters)
- `401` - Unauthorized (authentication required)
- `403` - Forbidden (insufficient permissions)
- `404` - Not Found (tag not found by slug)
- `405` - Method Not Allowed
- `422` - Unprocessable Entity (product not found, no valid tags, empty slugs)
- `500` - Internal Server Error (database errors)

## Authentication
All modification endpoints require authentication via the `protect_admin_employee.php` middleware:
- **Create:** Admin or Employee
- **Update:** Admin or Employee
- **Delete:** Admin or Employee
- **Get:** Public access

## Features

### Flexible Tag Input
- Support for both tag IDs and slugs in the same request
- Automatic deduplication of tag inputs
- Validation of tag existence before association

### Auto-Creation of Tags
- When `auto_create` is true, non-existent tags are created from slugs
- Automatic name generation from slugs (e.g., "full-frame" → "Full Frame")
- Race condition handling for concurrent tag creation

### Duplicate Prevention
- Uses `INSERT IGNORE` to prevent duplicate product-tag relationships
- Automatic handling of existing associations

### Transaction Safety
- Update operations use database transactions
- Automatic rollback on errors
- Consistent state maintenance

### Product Integration
- Only active products are returned in tag-based product queries
- Includes primary image URL for product listings
- Ordered results for consistent display

### Efficient Querying
- Optimized JOIN queries for relationship data
- Proper indexing on foreign keys
- Pagination support for large datasets

## Common Use Cases

### Product Tagging Workflow
1. **Initial Tagging:** Use create endpoint with existing tag IDs
2. **Add New Tags:** Use create endpoint with `auto_create` for new concepts
3. **Bulk Update:** Use update endpoint to replace all tags at once
4. **Selective Removal:** Use delete endpoint to remove specific tags
5. **Complete Reset:** Use delete endpoint with `all: true`

### Tag-Based Product Discovery
1. **Browse by Tag:** Use get endpoint with tag slug for customer browsing
2. **Related Products:** Find products sharing similar tags
3. **Tag Analytics:** Use get_all endpoint for administrative insights

## cURL Examples

### Get Tags for Product
```bash
curl -X GET "http://localhost/api/product_tags/get.php?product_id=1"
```

### Get Products by Tag
```bash
curl -X GET "http://localhost/api/product_tags/get.php?tag_slug=professional-camera&page=1&limit=5"
```

### Add Tags to Product
```bash
curl -X POST "http://localhost/api/product_tags/create.php" \
  -H "Content-Type: application/json" \
  -d '{
    "product_id": 1,
    "tag_ids": [5, 8, 12]
  }'
```

### Add Tags by Slug with Auto-Creation
```bash
curl -X POST "http://localhost/api/product_tags/create.php" \
  -H "Content-Type: application/json" \
  -d '{
    "product_id": 1,
    "slugs": ["new-feature", "limited-edition"],
    "auto_create": true
  }'
```

### Replace All Product Tags
```bash
curl -X PUT "http://localhost/api/product_tags/update.php" \
  -H "Content-Type: application/json" \
  -d '{
    "product_id": 1,
    "slugs": ["professional", "full-frame", "weather-sealed"]
  }'
```

### Remove Specific Tags
```bash
curl -X DELETE "http://localhost/api/product_tags/delete.php" \
  -H "Content-Type: application/json" \
  -d '{
    "product_id": 1,
    "tag_ids": [5, 8]
  }'
```

### Remove All Tags from Product
```bash
curl -X DELETE "http://localhost/api/product_tags/delete.php" \
  -H "Content-Type: application/json" \
  -d '{
    "product_id": 1,
    "all": true
  }'
```

### Get All Relationships (Admin)
```bash
curl -X GET "http://localhost/api/product_tags/get.php?get_all=1&page=1&limit=50"
```

## Notes

1. **Relationship Management:** This is a pure junction table API - it only manages relationships
2. **Product Validation:** All operations verify that the product_id exists
3. **Tag Validation:** Tag IDs are verified before creating relationships
4. **Cascading Deletes:** Relationships are automatically removed when products or tags are deleted
5. **Duplicate Handling:** Duplicate relationships are silently ignored
6. **Auto-Creation:** Only works with slugs, not with tag IDs
7. **Transaction Safety:** Update operations are atomic
8. **Performance:** Optimized for both product-to-tags and tag-to-products queries
9. **Flexibility:** Supports multiple input methods (IDs, slugs, mixed)
10. **Data Integrity:** Foreign key constraints ensure referential integrity