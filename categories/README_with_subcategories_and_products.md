# Categories API with Subcategories and Products

This endpoint provides categories with their associated subcategories and products in a nested structure.

## Endpoint

```
GET /api/categories/get_with_subcategories_and_products.php
```

## Features

- Get all categories with nested subcategories and products
- Get single category by ID with all its subcategories and products
- Search categories by name or slug
- Pagination support
- Only returns active categories, subcategories, and products
- Includes product counts for each category and subcategory
- Includes primary product images

## Parameters

### Get Single Category
- `id` (integer): Category ID to retrieve

### Get All Categories (with pagination)
- `page` (integer, optional): Page number (default: 1)
- `limit` (integer, optional): Items per page (default: 10, max: 50)
- `q` (string, optional): Search term for category name or slug

## Response Format

### Single Category Response
```json
{
  "success": true,
  "data": {
    "id": 1,
    "name": "Cameras",
    "slug": "cameras",
    "image_url": "https://example.com/camera.jpg",
    "is_active": 1,
    "created_at": "2024-01-01 12:00:00",
    "updated_at": "2024-01-01 12:00:00",
    "subcategories": [
      {
        "id": 1,
        "name": "DSLR Cameras",
        "slug": "dslr-cameras",
        "type": "photography",
        "category_id": 1,
        "image_url": "https://example.com/dslr.jpg",
        "is_active": 1,
        "created_at": "2024-01-01 12:00:00",
        "updated_at": "2024-01-01 12:00:00",
        "product_count": 5,
        "products": [
          {
            "id": 1,
            "name": "Canon EOS 5D Mark IV",
            "slug": "canon-eos-5d-mark-iv",
            "price": "2499.99",
            "type": "photography",
            "brand": "Canon",
            "is_active": 1,
            "primary_image_url": "https://example.com/canon-5d.jpg"
          }
        ]
      }
    ]
  }
}
```

### Multiple Categories Response
```json
{
  "success": true,
  "data": [
    {
      "id": 1,
      "name": "Cameras",
      "slug": "cameras",
      "image_url": "https://example.com/camera.jpg",
      "is_active": 1,
      "created_at": "2024-01-01 12:00:00",
      "updated_at": "2024-01-01 12:00:00",
      "subcategory_count": 3,
      "product_count": 15,
      "subcategories": [
        {
          "id": 1,
          "name": "DSLR Cameras",
          "slug": "dslr-cameras",
          "type": "photography",
          "category_id": 1,
          "image_url": "https://example.com/dslr.jpg",
          "is_active": 1,
          "created_at": "2024-01-01 12:00:00",
          "updated_at": "2024-01-01 12:00:00",
          "product_count": 5,
          "products": [
            {
              "id": 1,
              "name": "Canon EOS 5D Mark IV",
              "slug": "canon-eos-5d-mark-iv",
              "price": "2499.99",
              "type": "photography",
              "brand": "Canon",
              "is_active": 1,
              "primary_image_url": "https://example.com/canon-5d.jpg"
            }
          ]
        }
      ]
    }
  ],
  "pagination": {
    "page": 1,
    "limit": 10,
    "total": 25,
    "pages": 3,
    "has_next": true,
    "has_prev": false
  }
}
```

## Usage Examples

### Get all categories with subcategories and products
```bash
GET /api/categories/get_with_subcategories_and_products.php
```

### Get specific category by ID
```bash
GET /api/categories/get_with_subcategories_and_products.php?id=1
```

### Search categories
```bash
GET /api/categories/get_with_subcategories_and_products.php?q=camera
```

### Pagination
```bash
GET /api/categories/get_with_subcategories_and_products.php?page=2&limit=5
```

## Performance Notes

- For performance reasons, products are limited to:
  - 10 products per subcategory when getting a single category
  - 5 products per subcategory when getting multiple categories
- Only active categories, subcategories, and products are returned
- The endpoint uses efficient JOIN queries to minimize database calls

## Error Responses

### 404 Not Found (Single Category)
```json
{
  "error": "Category not found"
}
```

### 405 Method Not Allowed
```json
{
  "error": "Method not allowed"
}
```

### 500 Internal Server Error
```json
{
  "error": "Database error occurred"
}
```

## Database Schema Requirements

This endpoint requires the following tables:
- `categories` (id, name, slug, image_url, is_active, created_at, updated_at)
- `subcategories` (id, name, slug, type, category_id, image_url, is_active, created_at, updated_at)
- `products` (id, name, slug, price, type, brand, subcategory_id, is_active, created_at, updated_at)
- `product_images` (id, product_id, image_url, is_primary)