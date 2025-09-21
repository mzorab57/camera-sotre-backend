# Product Specifications API Documentation

## Overview
The Product Specifications API provides endpoints for managing detailed product specifications in the photography store system. Specifications can be organized into groups and support custom display ordering for better presentation.

## Base URL
```
http://localhost/api/product_specifications/
```

## Database Schema
```sql
CREATE TABLE IF NOT EXISTS product_specifications (
  id INT PRIMARY KEY AUTO_INCREMENT,
  product_id INT NOT NULL,
  spec_name VARCHAR(255) NOT NULL,
  spec_value TEXT NOT NULL,
  spec_group VARCHAR(100),
  display_order INT DEFAULT 0,
  created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
  updated_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
  FOREIGN KEY (product_id) REFERENCES products(id) ON DELETE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;
```

## Endpoints

### 1. GET Product Specifications
**Endpoint:** `GET /get.php`

#### Get Single Specification by ID
```
GET /get.php?id=1
```

#### Get All Specifications for a Product
```
GET /get.php?product_id=1
```

#### Get Specifications by Group
```
GET /get.php?product_id=1&spec_group=Camera%20Body
```

#### Get Grouped Specifications
```
GET /get.php?product_id=1&grouped=1
```

#### Query Parameters
- `id` (integer): Get specific specification by ID
- `product_id` (integer, required if no id): Get all specifications for a product
- `spec_group` (string): Filter by specification group
- `grouped` (boolean): Return specifications grouped by `spec_group`

#### Response Format (Normal)
```json
{
  "success": true,
  "data": [
    {
      "id": 1,
      "product_id": 1,
      "spec_name": "Sensor Type",
      "spec_value": "Full-frame CMOS",
      "spec_group": "Camera Body",
      "display_order": 0,
      "created_at": "2024-01-15 10:30:00",
      "updated_at": "2024-01-15 10:30:00"
    },
    {
      "id": 2,
      "product_id": 1,
      "spec_name": "Resolution",
      "spec_value": "45 Megapixels",
      "spec_group": "Camera Body",
      "display_order": 10,
      "created_at": "2024-01-15 10:31:00",
      "updated_at": "2024-01-15 10:31:00"
    }
  ]
}
```

#### Response Format (Grouped)
```json
{
  "success": true,
  "data": {
    "Camera Body": [
      {
        "id": 1,
        "product_id": 1,
        "spec_name": "Sensor Type",
        "spec_value": "Full-frame CMOS",
        "spec_group": "Camera Body",
        "display_order": 0
      }
    ],
    "Video": [
      {
        "id": 3,
        "product_id": 1,
        "spec_name": "Video Resolution",
        "spec_value": "8K at 30fps",
        "spec_group": "Video",
        "display_order": 0
      }
    ],
    "": [
      {
        "id": 4,
        "product_id": 1,
        "spec_name": "Weight",
        "spec_value": "650g",
        "spec_group": null,
        "display_order": 100
      }
    ]
  }
}
```

### 2. Create Product Specifications
**Endpoint:** `POST /create.php`
**Authentication:** Admin/Employee required

#### Create Single Specification
```json
{
  "product_id": 1,
  "spec_name": "Sensor Type",
  "spec_value": "Full-frame CMOS",
  "spec_group": "Camera Body",
  "display_order": 0
}
```

#### Create Multiple Specifications (Batch)
```json
{
  "product_id": 1,
  "start_order": 0,
  "specs": [
    {
      "spec_name": "Sensor Type",
      "spec_value": "Full-frame CMOS",
      "spec_group": "Camera Body",
      "display_order": 0
    },
    {
      "spec_name": "Resolution",
      "spec_value": "45 Megapixels",
      "spec_group": "Camera Body",
      "display_order": 10
    },
    {
      "spec_name": "ISO Range",
      "spec_value": "100-51200 (expandable to 102400)",
      "spec_group": "Performance"
    }
  ]
}
```

#### Form Data with JSON (Alternative)
```
POST /create.php
Content-Type: multipart/form-data

product_id=1
start_order=0
specs_json=[{"spec_name":"Sensor Type","spec_value":"Full-frame CMOS","spec_group":"Camera Body"}]
```

#### Request Parameters

**Single Specification:**
- `product_id` (integer, required): Product ID that must exist
- `spec_name` (string, required): Specification name
- `spec_value` (string, required): Specification value
- `spec_group` (string, optional): Group name for organization
- `display_order` (integer, optional): Custom display order

**Batch Specifications:**
- `product_id` (integer, required): Product ID that must exist
- `specs` (array, required): Array of specification objects
- `start_order` (integer, optional): Starting display order (auto-increments by 10)

**Each spec object in `specs` array:**
- `spec_name` (string, required): Specification name
- `spec_value` (string, required): Specification value
- `spec_group` (string, optional): Group name
- `display_order` (integer, optional): Custom display order

#### Response Format
```json
{
  "success": true,
  "data": [
    {
      "id": 1,
      "product_id": 1,
      "spec_name": "Sensor Type",
      "spec_value": "Full-frame CMOS",
      "spec_group": "Camera Body",
      "display_order": 0,
      "created_at": "2024-01-15 10:30:00",
      "updated_at": "2024-01-15 10:30:00"
    }
  ],
  "inserted_ids": [1, 2, 3]
}
```

### 3. Update Product Specification
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
  "spec_name": "Updated Sensor Type",
  "spec_value": "Full-frame BSI CMOS",
  "spec_group": "Camera Body",
  "display_order": 5
}
```

#### Move to Different Product
```json
{
  "id": 1,
  "product_id": 2,
  "display_order": 0
}
```

#### Updatable Fields
- `product_id` (integer): Move specification to different product
- `spec_name` (string): Update specification name
- `spec_value` (string): Update specification value
- `spec_group` (string): Update group (empty string sets to null)
- `display_order` (integer): Update display order

### 4. Delete Product Specification
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
  "message": "Spec deleted",
  "data": [
    {
      "id": 2,
      "product_id": 1,
      "spec_name": "Resolution",
      "spec_value": "45 Megapixels",
      "spec_group": "Camera Body",
      "display_order": 10
    }
  ]
}
```

## Error Codes
- `400` - Bad Request (missing required fields, no fields to update)
- `401` - Unauthorized (authentication required)
- `403` - Forbidden (insufficient permissions)
- `404` - Not Found (specification or product doesn't exist)
- `405` - Method Not Allowed
- `422` - Unprocessable Entity (validation errors, product not found, empty values)
- `500` - Internal Server Error (database errors)

## Authentication
All modification endpoints require authentication via the `protect_admin_employee.php` middleware:
- **Create:** Admin or Employee
- **Update:** Admin or Employee
- **Delete:** Admin or Employee
- **Get:** Public access

## Features

### Specification Groups
- Specifications can be organized into logical groups (e.g., "Camera Body", "Video", "Performance")
- Groups are optional - specifications without groups are still valid
- Grouped response format organizes specifications by their group
- Empty group name is treated as null/ungrouped

### Display Ordering
- Specifications support custom display ordering within each product
- Automatic ordering: `MAX(display_order) + 10` if not specified
- Batch creation can use `start_order` with auto-increment by 10
- Individual specifications can override with custom `display_order`

### Sorting Logic
Specifications are returned in this order:
1. Ungrouped specifications first (`spec_group IS NULL`)
2. Then grouped specifications (`spec_group`)
3. Within each group: by display order (`display_order ASC`)
4. Finally by specification name (`spec_name ASC`)

### Batch Operations
- Multiple specifications can be created in a single request
- Supports both JSON array and form-data with JSON string
- Automatic display order management for batch inserts
- Returns all inserted specifications with their new IDs

## Common Specification Groups

### Camera Products
- **Camera Body**: Sensor, Mount, Build
- **Performance**: ISO, Autofocus, Speed
- **Video**: Resolution, Frame rates, Codecs
- **Connectivity**: Ports, Wireless, Storage
- **Physical**: Dimensions, Weight, Weather sealing

### Lens Products
- **Optics**: Focal length, Aperture, Elements
- **Features**: Image stabilization, Autofocus, Coatings
- **Build**: Weather sealing, Filter size, Weight
- **Compatibility**: Mount, Format coverage

### Accessory Products
- **Specifications**: Technical specs
- **Compatibility**: Supported devices/formats
- **Physical**: Dimensions, Weight, Materials
- **Features**: Special capabilities

## cURL Examples

### Get All Specifications for Product
```bash
curl -X GET "http://localhost/api/product_specifications/get.php?product_id=1"
```

### Get Grouped Specifications
```bash
curl -X GET "http://localhost/api/product_specifications/get.php?product_id=1&grouped=1"
```

### Create Single Specification
```bash
curl -X POST "http://localhost/api/product_specifications/create.php" \
  -H "Content-Type: application/json" \
  -d '{
    "product_id": 1,
    "spec_name": "Sensor Type",
    "spec_value": "Full-frame CMOS",
    "spec_group": "Camera Body"
  }'
```

### Create Multiple Specifications
```bash
curl -X POST "http://localhost/api/product_specifications/create.php" \
  -H "Content-Type: application/json" \
  -d '{
    "product_id": 1,
    "specs": [
      {
        "spec_name": "Sensor Type",
        "spec_value": "Full-frame CMOS",
        "spec_group": "Camera Body"
      },
      {
        "spec_name": "Resolution",
        "spec_value": "45 Megapixels",
        "spec_group": "Camera Body"
      }
    ]
  }'
```

### Update Specification
```bash
curl -X PUT "http://localhost/api/product_specifications/update.php?id=1" \
  -H "Content-Type: application/json" \
  -d '{
    "spec_value": "Full-frame BSI CMOS",
    "display_order": 5
  }'
```

### Delete Specification
```bash
curl -X DELETE "http://localhost/api/product_specifications/delete.php?id=1"
```

## Notes

1. **Product Validation:** All operations verify that the `product_id` exists in the products table
2. **Required Fields:** Both `spec_name` and `spec_value` are required and cannot be empty
3. **Group Management:** Specifications can be moved between groups or made ungrouped
4. **Display Order:** Automatic ordering ensures proper sequence without manual management
5. **Batch Efficiency:** Multiple specifications can be created efficiently in a single request
6. **Flexible Grouping:** Groups are optional and can be added/removed as needed
7. **Cascading Delete:** Specifications are automatically deleted when their parent product is deleted
8. **Product Migration:** Specifications can be moved between products if needed