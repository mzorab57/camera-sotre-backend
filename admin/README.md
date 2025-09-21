# Admin API Documentation

This directory contains administrative endpoints for the API system. These endpoints provide dashboard statistics and administrative functionality for managing the application.

## Overview

The admin API provides:
- **Dashboard Statistics**: System-wide counts and metrics
- **Latest Content**: Recent additions to the system
- **Administrative Insights**: Data for admin dashboards

## Base URL
```
/api/admin/
```

## Authentication

All admin endpoints require authentication and admin/employee role permissions:
- **Authentication**: Bearer token required
- **Authorization**: Admin or Employee role required
- **Middleware**: Uses `protect_admin_employee.php`

## Endpoints

### Get Dashboard Statistics

**Endpoint:** `GET /api/admin/stats.php`

**Description:** Retrieves comprehensive dashboard statistics including entity counts and latest products.

**Authentication:** Required (Admin/Employee)

**Request:**
```http
GET /api/admin/stats.php
Authorization: Bearer <access_token>
Content-Type: application/json
```

**Response:**
```json
{
  "success": true,
  "counts": {
    "products_total": 1250,
    "products_active": 1180,
    "categories": 15,
    "subcategories": 45,
    "brands": 28,
    "tags": 156,
    "images": 3420
  },
  "latest_products": [
    {
      "id": 1001,
      "name": "Canon EOS R5 Mirrorless Camera",
      "slug": "canon-eos-r5-mirrorless-camera",
      "price": "3899.00",
      "brand": "Canon",
      "created_at": "2024-01-15 14:30:25",
      "primary_image_url": "/uploads/products/canon-eos-r5-main.jpg"
    },
    {
      "id": 1000,
      "name": "Sony A7 IV Full Frame Camera",
      "slug": "sony-a7-iv-full-frame-camera",
      "price": "2498.00",
      "brand": "Sony",
      "created_at": "2024-01-15 12:15:10",
      "primary_image_url": "/uploads/products/sony-a7-iv-main.jpg"
    }
  ]
}
```

**Response Fields:**

#### Counts Object
- `products_total` (integer): Total number of products in the system
- `products_active` (integer): Number of active products (`is_active = 1`)
- `categories` (integer): Total number of product categories
- `subcategories` (integer): Total number of subcategories
- `brands` (integer): Total number of brands
- `tags` (integer): Total number of tags
- `images` (integer): Total number of product images

#### Latest Products Array
Array of the 10 most recently created products, each containing:
- `id` (integer): Product ID
- `name` (string): Product name
- `slug` (string): URL-friendly product identifier
- `price` (string): Product price (decimal format)
- `brand` (string): Product brand name
- `created_at` (string): Creation timestamp (YYYY-MM-DD HH:MM:SS)
- `primary_image_url` (string|null): URL of the primary product image

**Error Responses:**

```json
{
  "error": "Missing Authorization Bearer token"
}
```
*Status: 401 Unauthorized*

```json
{
  "error": "Forbidden: insufficient role"
}
```
*Status: 403 Forbidden*

```json
{
  "error": "Server error"
}
```
*Status: 500 Internal Server Error*

## Usage Examples

### JavaScript/Fetch API
```javascript
// Get dashboard statistics
const response = await fetch('/api/admin/stats.php', {
  method: 'GET',
  headers: {
    'Authorization': `Bearer ${accessToken}`,
    'Content-Type': 'application/json'
  }
});

const data = await response.json();

if (data.success) {
  console.log('Total Products:', data.counts.products_total);
  console.log('Active Products:', data.counts.products_active);
  console.log('Latest Products:', data.latest_products);
} else {
  console.error('Error:', data.error);
}
```

### cURL
```bash
# Get dashboard statistics
curl -X GET "http://localhost/api/admin/stats.php" \
  -H "Authorization: Bearer YOUR_ACCESS_TOKEN" \
  -H "Content-Type: application/json"
```

### PHP
```php
$ch = curl_init();
curl_setopt($ch, CURLOPT_URL, 'http://localhost/api/admin/stats.php');
curl_setopt($ch, CURLOPT_HTTPHEADER, [
    'Authorization: Bearer ' . $accessToken,
    'Content-Type: application/json'
]);
curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);

$response = curl_exec($ch);
$data = json_decode($response, true);

if ($data['success']) {
    echo "Total Products: " . $data['counts']['products_total'];
    echo "Latest Products: " . count($data['latest_products']);
}

curl_close($ch);
```

## Dashboard Integration Examples

### React Dashboard Component
```jsx
import React, { useState, useEffect } from 'react';

function AdminDashboard() {
  const [stats, setStats] = useState(null);
  const [loading, setLoading] = useState(true);

  useEffect(() => {
    fetchStats();
  }, []);

  const fetchStats = async () => {
    try {
      const response = await fetch('/api/admin/stats.php', {
        headers: {
          'Authorization': `Bearer ${localStorage.getItem('accessToken')}`,
          'Content-Type': 'application/json'
        }
      });
      
      const data = await response.json();
      if (data.success) {
        setStats(data);
      }
    } catch (error) {
      console.error('Failed to fetch stats:', error);
    } finally {
      setLoading(false);
    }
  };

  if (loading) return <div>Loading...</div>;
  if (!stats) return <div>Error loading dashboard</div>;

  return (
    <div className="dashboard">
      <h1>Admin Dashboard</h1>
      
      <div className="stats-grid">
        <div className="stat-card">
          <h3>Products</h3>
          <p>{stats.counts.products_active} / {stats.counts.products_total}</p>
          <small>Active / Total</small>
        </div>
        
        <div className="stat-card">
          <h3>Categories</h3>
          <p>{stats.counts.categories}</p>
        </div>
        
        <div className="stat-card">
          <h3>Brands</h3>
          <p>{stats.counts.brands}</p>
        </div>
        
        <div className="stat-card">
          <h3>Images</h3>
          <p>{stats.counts.images}</p>
        </div>
      </div>
      
      <div className="latest-products">
        <h2>Latest Products</h2>
        <div className="products-list">
          {stats.latest_products.map(product => (
            <div key={product.id} className="product-item">
              <img src={product.primary_image_url} alt={product.name} />
              <div>
                <h4>{product.name}</h4>
                <p>Brand: {product.brand}</p>
                <p>Price: ${product.price}</p>
                <small>{new Date(product.created_at).toLocaleDateString()}</small>
              </div>
            </div>
          ))}
        </div>
      </div>
    </div>
  );
}

export default AdminDashboard;
```

### Vue.js Dashboard Component
```vue
<template>
  <div class="admin-dashboard">
    <h1>Admin Dashboard</h1>
    
    <div v-if="loading" class="loading">Loading...</div>
    
    <div v-else-if="stats" class="dashboard-content">
      <!-- Statistics Cards -->
      <div class="stats-grid">
        <div class="stat-card">
          <h3>Products</h3>
          <div class="stat-value">
            {{ stats.counts.products_active }} / {{ stats.counts.products_total }}
          </div>
          <div class="stat-label">Active / Total</div>
        </div>
        
        <div class="stat-card">
          <h3>Categories</h3>
          <div class="stat-value">{{ stats.counts.categories }}</div>
        </div>
        
        <div class="stat-card">
          <h3>Subcategories</h3>
          <div class="stat-value">{{ stats.counts.subcategories }}</div>
        </div>
        
        <div class="stat-card">
          <h3>Brands</h3>
          <div class="stat-value">{{ stats.counts.brands }}</div>
        </div>
        
        <div class="stat-card">
          <h3>Tags</h3>
          <div class="stat-value">{{ stats.counts.tags }}</div>
        </div>
        
        <div class="stat-card">
          <h3>Images</h3>
          <div class="stat-value">{{ stats.counts.images }}</div>
        </div>
      </div>
      
      <!-- Latest Products -->
      <div class="latest-products">
        <h2>Latest Products</h2>
        <div class="products-grid">
          <div 
            v-for="product in stats.latest_products" 
            :key="product.id" 
            class="product-card"
          >
            <img 
              :src="product.primary_image_url" 
              :alt="product.name"
              class="product-image"
            />
            <div class="product-info">
              <h4>{{ product.name }}</h4>
              <p class="brand">{{ product.brand }}</p>
              <p class="price">${{ product.price }}</p>
              <p class="date">{{ formatDate(product.created_at) }}</p>
            </div>
          </div>
        </div>
      </div>
    </div>
    
    <div v-else class="error">
      Error loading dashboard data
    </div>
  </div>
</template>

<script>
export default {
  name: 'AdminDashboard',
  data() {
    return {
      stats: null,
      loading: true,
      error: null
    }
  },
  
  async mounted() {
    await this.fetchStats();
  },
  
  methods: {
    async fetchStats() {
      try {
        const response = await fetch('/api/admin/stats.php', {
          headers: {
            'Authorization': `Bearer ${this.$store.state.auth.accessToken}`,
            'Content-Type': 'application/json'
          }
        });
        
        const data = await response.json();
        
        if (data.success) {
          this.stats = data;
        } else {
          this.error = data.error || 'Unknown error';
        }
      } catch (error) {
        this.error = 'Failed to fetch dashboard data';
        console.error('Dashboard error:', error);
      } finally {
        this.loading = false;
      }
    },
    
    formatDate(dateString) {
      return new Date(dateString).toLocaleDateString();
    }
  }
}
</script>
```

## Data Analysis Examples

### Calculate Product Activity Rate
```javascript
const stats = await fetchStats();
const activityRate = (stats.counts.products_active / stats.counts.products_total * 100).toFixed(1);
console.log(`Product Activity Rate: ${activityRate}%`);
```

### Average Images per Product
```javascript
const stats = await fetchStats();
const avgImages = (stats.counts.images / stats.counts.products_total).toFixed(1);
console.log(`Average Images per Product: ${avgImages}`);
```

### Subcategories per Category
```javascript
const stats = await fetchStats();
const avgSubcategories = (stats.counts.subcategories / stats.counts.categories).toFixed(1);
console.log(`Average Subcategories per Category: ${avgSubcategories}`);
```

## Performance Considerations

- **Caching**: Consider caching statistics for frequently accessed dashboards
- **Indexing**: Ensure proper database indexes on `created_at` and `is_active` columns
- **Pagination**: For large datasets, consider paginating latest products
- **Real-time Updates**: Implement WebSocket or polling for real-time statistics

## Security Features

- **Role-based Access**: Only admin and employee users can access statistics
- **Token Validation**: JWT tokens are validated on each request
- **SQL Injection Protection**: Uses parameterized queries (though not needed for these specific queries)
- **Data Sanitization**: All output is properly encoded in JSON

## Dependencies

- `middleware/protect_admin_employee.php`: Authentication and authorization
- `config/db.php`: Database connection (via middleware)
- Database tables: `products`, `categories`, `subcategories`, `brands`, `tags`, `product_images`

## Database Requirements

The following tables must exist with proper structure:

```sql
-- Required tables for statistics
products (id, name, slug, price, brand, is_active, created_at)
categories (id, ...)
subcategories (id, ...)
brands (id, ...)
tags (id, ...)
product_images (id, product_id, image_url, is_primary)
```

## Error Handling

### Database Connection Errors
```php
try {
    $pdo = db();
    // Statistics queries...
} catch (PDOException $e) {
    http_response_code(500);
    echo json_encode(['error' => 'Database connection failed']);
    exit;
}
```

### Query Execution Errors
```php
try {
    $counts['products_total'] = (int)$pdo->query("SELECT COUNT(*) FROM products")->fetchColumn();
} catch (PDOException $e) {
    error_log("Stats query error: " . $e->getMessage());
    $counts['products_total'] = 0; // Fallback value
}
```

## Future Enhancements

Potential additions to the admin API:

1. **Time-based Statistics**: Daily, weekly, monthly trends
2. **Revenue Analytics**: Sales data and financial metrics
3. **User Activity**: Login statistics and user engagement
4. **Inventory Alerts**: Low stock and out-of-stock notifications
5. **Performance Metrics**: API response times and error rates
6. **Export Functionality**: CSV/Excel export of statistics
7. **Real-time Notifications**: WebSocket-based live updates
8. **Advanced Filtering**: Date ranges and custom filters

## Notes

- Statistics are calculated in real-time on each request
- Latest products are ordered by creation date (newest first)
- Primary images are automatically selected for latest products
- All counts return integers for consistent data types
- The endpoint is optimized for dashboard display purposes
- Consider implementing caching for high-traffic admin dashboards