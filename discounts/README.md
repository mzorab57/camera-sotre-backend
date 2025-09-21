# Discounts API Documentation

This API provides CRUD operations for managing discounts in your e-commerce system with **hierarchical discount inheritance**.

## Overview

The Discounts API provides functionality to create, read, update, and delete discount rules that can be applied to products, categories, or subcategories. The system supports both percentage-based and fixed-amount discounts with flexible targeting and scheduling options.

### 🎯 **Hierarchical Discount System**
The discount system implements a **three-level hierarchy** with automatic inheritance:

1. **Product Level** (Highest Priority) - Direct product discounts
2. **Subcategory Level** (Medium Priority) - Applies to all products in the subcategory
3. **Category Level** (Lowest Priority) - Applies to all products in the category

**Inheritance Rules:**
- Products inherit discounts from their subcategory if no direct product discount exists
- Products inherit discounts from their category if no subcategory or product discount exists
- Higher priority discounts always override lower priority ones
- Within the same level, higher priority values take precedence

## Database Setup

1. Run the SQL commands from `sample_data.sql` to create the discounts table and insert sample data.
2. Update the database connection parameters in all PHP files:
   - Host: `localhost`
   - Database: `your_database`
   - Username: `username`
   - Password: `password`

## API Endpoints

### 1. Get Discounts (GET)

**Endpoint:** `GET /api/discounts/get.php`

#### Get All Discounts
```
GET /api/discounts/get.php
GET /api/discounts/get.php?page=1&limit=10
```

#### Get Specific Discount
```
GET /api/discounts/get.php?id=1
```

#### Filter Parameters
- `page` - Page number (default: 1)
- `limit` - Items per page (default: 10, max: 100)
- `is_active` - Filter by active status (true/false)
- `discount_type` - Filter by type (percentage/fixed_amount)
- `target_type` - Filter by target (product/category/subcategory)
- `target_id` - Filter by specific target ID
- `search` - Search by discount name
- `start_date` - Filter by start date (YYYY-MM-DD)
- `end_date` - Filter by end date (YYYY-MM-DD)
- `currently_active` - Show only currently active discounts (true)

#### Response Format
```json
{
  "success": true,
  "data": [
    {
      "id": 1,
      "name": "Summer Sale 2024",
      "description": "20% off on all electronics",
      "discount_type": "percentage",
      "discount_value": "20.00",
      "target_type": "category",
      "target_id": 1,
      "start_date": "2024-06-01 00:00:00",
      "end_date": "2024-08-31 23:59:59",
      "is_active": 1,
      "priority": 10,
      "max_uses": 1000,
      "used_count": 0,
      "min_order_amount": "50.00",
      "created_at": "2024-01-01 12:00:00",
      "updated_at": "2024-01-01 12:00:00"
    }
  ],
  "pagination": {
    "current_page": 1,
    "total_pages": 5,
    "total_count": 50,
    "limit": 10,
    "has_next": true,
    "has_prev": false
  }
}
```

### 2. Calculate Product Discounts 🆕

**Endpoint:** `GET /api/discounts/calculate.php`

Calculate the best applicable discount for a product using hierarchical inheritance.

#### Parameters
- `product_id` (required): Product ID to calculate discount for
- `product_ids` (alternative): Comma-separated list of product IDs for bulk calculation

#### Single Product Example
```
GET /api/discounts/calculate.php?product_id=123
```

#### Response
```json
{
  "success": true,
  "data": {
    "product": {
      "id": 123,
      "name": "Canon EOS R5",
      "category_name": "Cameras",
      "subcategory_name": "Mirrorless Cameras"
    },
    "pricing": {
      "original_price": 3899.00,
      "final_price": 3119.20,
      "discount_amount": 779.80,
      "discount_percentage": 20.0
    },
    "discount": {
      "id": 5,
      "name": "Camera Sale 2024",
      "description": "20% off all cameras",
      "type": "percentage",
      "value": 20.0,
      "target_type": "category",
      "target_id": 1,
      "priority": 10,
      "start_date": "2024-01-01 00:00:00",
      "end_date": "2024-12-31 23:59:59"
    }
  }
}
```

#### Bulk Calculation Example
```
GET /api/discounts/calculate.php?product_ids=123,124,125
```

### 3. List Products with Discounts 🆕

**Endpoint:** `GET /api/products/list_with_discounts.php`

Get products with their calculated discounts and inheritance information.

#### Parameters
- `page` (optional): Page number (default: 1)
- `limit` (optional): Items per page (default: 20, max: 100)
- `category_id` (optional): Filter by category
- `subcategory_id` (optional): Filter by subcategory
- `brand` (optional): Filter by brand
- `type` (optional): Filter by product type
- `is_featured` (optional): Filter featured products
- `min_price` (optional): Minimum price filter
- `max_price` (optional): Maximum price filter
- `search` (optional): Search in name, model, SKU, description
- `discounted_only` (optional): Show only products with active discounts

#### Example Request
```
GET /api/products/list_with_discounts.php?category_id=1&discounted_only=1&page=1&limit=10
```

#### Response
```json
{
  "success": true,
  "data": [
    {
      "id": 123,
      "name": "Canon EOS R5",
      "model": "EOS R5",
      "slug": "canon-eos-r5",
      "sku": "CAN-R5-001",
      "brand": "Canon",
      "is_featured": true,
      "primary_image": "/uploads/products/canon-r5.jpg",
      "category": {
        "id": 1,
        "name": "Cameras"
      },
      "subcategory": {
        "id": 2,
        "name": "Mirrorless Cameras"
      },
      "pricing": {
        "original_price": 3899.00,
        "final_price": 3119.20,
        "discount_amount": 779.80,
        "discount_percentage": 20.0,
        "has_discount": true
      },
      "discount": {
        "id": 5,
        "name": "Camera Sale 2024",
        "type": "percentage",
        "value": 20.0,
        "target_type": "category",
        "inheritance_level": "category"
      }
    }
  ],
  "pagination": {
    "current_page": 1,
    "total_pages": 3,
    "total_items": 25,
    "items_per_page": 10,
    "has_next": true,
    "has_prev": false
  },
  "statistics": {
    "total_products": 10,
    "discounted_products": 8,
    "discount_coverage": 80.0,
    "active_discounts": {
      "total": 15,
      "product_level": 5,
      "subcategory_level": 6,
      "category_level": 4
    }
  }
}
```

### 4. Create Discount (POST)

**Endpoint:** `POST /api/discounts/create.php`

#### Request Body
```json
{
  "name": "Black Friday Sale",
  "description": "50% off selected items",
  "discount_type": "percentage",
  "discount_value": 50.00,
  "target_type": "category",
  "target_id": 2,
  "start_date": "2024-11-29 00:00:00",
  "end_date": "2024-11-29 23:59:59",
  "is_active": true,
  "priority": 15,
  "max_uses": 500,
  "min_order_amount": 100.00
}
```

#### Required Fields
- `name` - Discount name
- `discount_type` - Type (percentage/fixed_amount)
- `discount_value` - Discount value
- `target_type` - Target type (product/category/subcategory)
- `target_id` - Target ID
- `start_date` - Start date (YYYY-MM-DD HH:MM:SS)

#### Optional Fields
- `description` - Discount description
- `end_date` - End date (null for no expiry)
- `is_active` - Active status (default: true)
- `priority` - Priority (default: 0)
- `max_uses` - Maximum uses (null for unlimited)
- `min_order_amount` - Minimum order amount

### 5. Update Discount (PUT)

**Endpoint:** `PUT /api/discounts/update.php?id=1`

#### Request Body
```json
{
  "name": "Updated Sale Name",
  "discount_value": 30.00,
  "is_active": false
}
```

**Note:** Only include fields you want to update. All fields from create are supported.

### 6. Delete Discount (DELETE)

**Endpoint:** `DELETE /api/discounts/delete.php?id=1`

#### Response
```json
{
  "success": true,
  "message": "Discount deleted successfully",
  "data": {
    "id": 1,
    "name": "Summer Sale 2024"
  }
}
```

## Error Responses

All endpoints return error responses in this format:

```json
{
  "success": false,
  "error": "Error message description"
}
```

### Common HTTP Status Codes
- `200` - Success
- `400` - Bad Request (validation error)
- `404` - Not Found
- `405` - Method Not Allowed
- `500` - Internal Server Error

## Validation Rules

### Discount Type Validation
- `percentage`: Value must be between 0 and 100
- `fixed_amount`: Value must be greater than 0

### Target Type Validation
- Must be one of: `product`, `category`, `subcategory`
- `target_id` must be a valid integer

### Date Validation
- `start_date` is required
- `end_date` must be after `start_date` (if provided)
- Dates should be in format: `YYYY-MM-DD HH:MM:SS`

## Usage Examples

### JavaScript/Fetch Examples

```javascript
// Get all active discounts
fetch('/api/discounts/get.php?is_active=true&currently_active=true')
  .then(response => response.json())
  .then(data => console.log(data));

// Create new discount
fetch('/api/discounts/create.php', {
  method: 'POST',
  headers: {
    'Content-Type': 'application/json'
  },
  body: JSON.stringify({
    name: 'New Year Sale',
    discount_type: 'percentage',
    discount_value: 25,
    target_type: 'category',
    target_id: 1,
    start_date: '2024-01-01 00:00:00',
    end_date: '2024-01-07 23:59:59'
  })
})
.then(response => response.json())
.then(data => console.log(data));

// Update discount
fetch('/api/discounts/update.php?id=1', {
  method: 'PUT',
  headers: {
    'Content-Type': 'application/json'
  },
  body: JSON.stringify({
    discount_value: 30,
    is_active: true
  })
})
.then(response => response.json())
.then(data => console.log(data));

// Delete discount
fetch('/api/discounts/delete.php?id=1', {
  method: 'DELETE'
})
.then(response => response.json())
.then(data => console.log(data));
```

### cURL Examples

```bash
# Get discounts
curl "http://localhost/api/discounts/get.php?page=1&limit=5"

# Create discount
curl -X POST "http://localhost/api/discounts/create.php" \
  -H "Content-Type: application/json" \
  -d '{
    "name": "Weekend Sale",
    "discount_type": "percentage",
    "discount_value": 15,
    "target_type": "product",
    "target_id": 5,
    "start_date": "2024-12-07 00:00:00",
    "end_date": "2024-12-08 23:59:59"
  }'

# Update discount
curl -X PUT "http://localhost/api/discounts/update.php?id=1" \
  -H "Content-Type: application/json" \
  -d '{"is_active": false}'

# Delete discount
curl -X DELETE "http://localhost/api/discounts/delete.php?id=1"
```

## Security Notes

1. **Database Credentials**: Update the database connection parameters in all PHP files with your actual credentials.
2. **Input Validation**: All inputs are validated and sanitized using prepared statements.
3. **CORS**: The API includes CORS headers for cross-origin requests.
4. **Error Handling**: Comprehensive error handling with appropriate HTTP status codes.

## Testing

1. Import the `sample_data.sql` to get test data
2. Use the provided cURL or JavaScript examples
3. Test all CRUD operations with various parameters
4. Verify validation rules work correctly
5. Test pagination and filtering functionality