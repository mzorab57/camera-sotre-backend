# API Utilities Documentation

This directory contains utility functions and helper classes used throughout the API system. These utilities provide common functionality for JWT token management and discount calculations.

## Overview

The utils directory includes:
- **JWT Utilities**: Token encoding, decoding, and validation
- **Discount Calculator**: Product discount calculation and application

## Base Path
```
/api/utils/
```

## Utility Components

### 1. JWT Utilities (`jwt.php`)

Provides JSON Web Token (JWT) functionality for authentication and authorization.

#### Functions

##### `b64url_encode(string $data): string`
Encodes data using base64url encoding (URL-safe base64).

**Parameters:**
- `$data` (string): Data to encode

**Returns:**
- `string`: Base64url encoded string

**Example:**
```php
$encoded = b64url_encode('Hello World');
// Returns: SGVsbG8gV29ybGQ
```

##### `b64url_decode(string $data): string`
Decodes base64url encoded data.

**Parameters:**
- `$data` (string): Base64url encoded string

**Returns:**
- `string`: Decoded data

**Example:**
```php
$decoded = b64url_decode('SGVsbG8gV29ybGQ');
// Returns: Hello World
```

##### `jwt_encode(array $payload, string $secret, int $ttl = 3600, array $header = []): string`
Creates a JWT token with the specified payload.

**Parameters:**
- `$payload` (array): Token payload data
- `$secret` (string): Secret key for signing
- `$ttl` (int): Time to live in seconds (default: 3600)
- `$header` (array): Optional header overrides

**Returns:**
- `string`: JWT token

**Example:**
```php
$payload = [
    'uid' => 123,
    'email' => 'user@example.com',
    'role' => 'admin'
];
$token = jwt_encode($payload, 'your-secret-key', 7200);
```

**Default Header:**
```json
{
  "alg": "HS256",
  "typ": "JWT"
}
```

**Auto-added Claims:**
- `iat` (issued at): Current timestamp
- `exp` (expires): Current timestamp + TTL

##### `jwt_decode(string $token, string $secret): array`
Decodes and validates a JWT token.

**Parameters:**
- `$token` (string): JWT token to decode
- `$secret` (string): Secret key for verification

**Returns:**
- `array`: Token payload

**Throws:**
- `Exception`: Invalid token format
- `Exception`: Invalid token encoding
- `Exception`: Unsupported algorithm
- `Exception`: Invalid signature
- `Exception`: Token expired

**Example:**
```php
try {
    $payload = jwt_decode($token, 'your-secret-key');
    echo "User ID: " . $payload['uid'];
} catch (Exception $e) {
    echo "Token validation failed: " . $e->getMessage();
}
```

#### Security Features

- **HMAC-SHA256 Signature**: Ensures token integrity
- **Expiration Validation**: Automatic token expiry checking
- **Timing Attack Protection**: Uses `hash_equals()` for signature comparison
- **URL-Safe Encoding**: Compatible with HTTP headers and URLs

### 2. Discount Calculator (`discount_calculator.php`)

Provides discount calculation functionality for products based on hierarchical rules.

#### Functions

##### `calculateProductDiscount($productId, $subcategoryId = null, $categoryId = null)`
Finds the best applicable discount for a product.

**Parameters:**
- `$productId` (int): Product ID
- `$subcategoryId` (int|null): Subcategory ID (optional)
- `$categoryId` (int|null): Category ID (optional)

**Returns:**
- `array|null`: Discount record or null if no discount applies

**Discount Priority (Highest to Lowest):**
1. **Product-specific** discounts
2. **Subcategory** discounts
3. **Category** discounts

**Within same target type:**
1. Higher `priority` value
2. Higher `discount_value`

**Example:**
```php
$discount = calculateProductDiscount(123, 45, 12);
if ($discount) {
    echo "Discount: " . $discount['discount_value'];
    echo "Type: " . $discount['discount_type'];
}
```

**Sample Return:**
```php
[
    'id' => 5,
    'name' => 'Summer Sale',
    'discount_type' => 'percentage',
    'discount_value' => 20.00,
    'target_type' => 'product',
    'target_id' => 123,
    'priority' => 10,
    'is_active' => 1,
    'start_date' => '2024-06-01 00:00:00',
    'end_date' => '2024-08-31 23:59:59'
]
```

##### `applyDiscount($originalPrice, $discount)`
Applies a discount to a price.

**Parameters:**
- `$originalPrice` (float): Original product price
- `$discount` (array|null): Discount record from `calculateProductDiscount()`

**Returns:**
- `float`: Final price after discount (minimum 0)

**Discount Types:**
- **percentage**: Reduces price by percentage
- **fixed**: Reduces price by fixed amount

**Example:**
```php
$originalPrice = 100.00;
$discount = calculateProductDiscount(123, 45, 12);
$finalPrice = applyDiscount($originalPrice, $discount);

echo "Original: $" . $originalPrice;
echo "Final: $" . $finalPrice;
```

**Calculation Examples:**
```php
// Percentage discount (20%)
$discount = ['discount_type' => 'percentage', 'discount_value' => 20];
$finalPrice = applyDiscount(100, $discount); // Returns: 80.00

// Fixed discount ($15)
$discount = ['discount_type' => 'fixed', 'discount_value' => 15];
$finalPrice = applyDiscount(100, $discount); // Returns: 85.00

// No discount
$finalPrice = applyDiscount(100, null); // Returns: 100.00
```

## Usage Examples

### JWT Token Management

#### Creating Access Tokens
```php
require_once __DIR__ . '/utils/jwt.php';

// Create access token (1 hour)
$payload = [
    'uid' => $user['id'],
    'email' => $user['email'],
    'role' => $user['role']
];
$accessToken = jwt_encode($payload, $_ENV['JWT_SECRET'], 3600);

// Create refresh token (30 days)
$refreshPayload = ['uid' => $user['id'], 'type' => 'refresh'];
$refreshToken = jwt_encode($refreshPayload, $_ENV['JWT_SECRET'], 2592000);
```

#### Validating Tokens
```php
require_once __DIR__ . '/utils/jwt.php';

try {
    $payload = jwt_decode($token, $_ENV['JWT_SECRET']);
    
    // Token is valid, use payload data
    $userId = $payload['uid'];
    $userRole = $payload['role'];
    
} catch (Exception $e) {
    // Handle invalid token
    http_response_code(401);
    echo json_encode(['error' => 'Invalid token: ' . $e->getMessage()]);
}
```

### Discount Calculations

#### Product Pricing with Discounts
```php
require_once __DIR__ . '/utils/discount_calculator.php';

// Get product data
$product = getProduct($productId); // Your function
$originalPrice = $product['price'];

// Calculate discount
$discount = calculateProductDiscount(
    $product['id'],
    $product['subcategory_id'],
    $product['category_id']
);

// Apply discount
$finalPrice = applyDiscount($originalPrice, $discount);
$savings = $originalPrice - $finalPrice;

// Response
echo json_encode([
    'product_id' => $product['id'],
    'original_price' => $originalPrice,
    'final_price' => $finalPrice,
    'savings' => $savings,
    'discount' => $discount ? [
        'name' => $discount['name'],
        'type' => $discount['discount_type'],
        'value' => $discount['discount_value']
    ] : null
]);
```

#### Bulk Discount Calculation
```php
require_once __DIR__ . '/utils/discount_calculator.php';

$products = getProducts(); // Your function
$pricedProducts = [];

foreach ($products as $product) {
    $discount = calculateProductDiscount(
        $product['id'],
        $product['subcategory_id'],
        $product['category_id']
    );
    
    $product['original_price'] = $product['price'];
    $product['final_price'] = applyDiscount($product['price'], $discount);
    $product['discount_info'] = $discount;
    
    $pricedProducts[] = $product;
}

echo json_encode($pricedProducts);
```

## Integration Examples

### Authentication Middleware Integration
```php
// middleware/require_auth.php uses jwt.php
require_once __DIR__ . '/../utils/jwt.php';

function require_auth(): array {
    $token = get_authorization_token();
    if (!$token) {
        http_response_code(401);
        echo json_encode(['error' => 'Missing token']);
        exit;
    }
    
    try {
        return jwt_decode($token, $_ENV['JWT_SECRET']);
    } catch (Exception $e) {
        http_response_code(401);
        echo json_encode(['error' => 'Invalid token']);
        exit;
    }
}
```

### Product API Integration
```php
// products/get.php uses discount_calculator.php
require_once __DIR__ . '/../utils/discount_calculator.php';

// Get product with pricing
$product = getProductById($id);

$discount = calculateProductDiscount(
    $product['id'],
    $product['subcategory_id'],
    $product['category_id']
);

$product['original_price'] = $product['price'];
$product['current_price'] = applyDiscount($product['price'], $discount);
$product['has_discount'] = $discount !== null;

if ($discount) {
    $product['discount'] = [
        'name' => $discount['name'],
        'type' => $discount['discount_type'],
        'value' => $discount['discount_value'],
        'savings' => $product['original_price'] - $product['current_price']
    ];
}

echo json_encode(['success' => true, 'data' => $product]);
```

## Error Handling

### JWT Errors
```php
try {
    $payload = jwt_decode($token, $secret);
} catch (Exception $e) {
    switch ($e->getMessage()) {
        case 'Invalid token format':
            // Token doesn't have 3 parts
            break;
        case 'Invalid token encoding':
            // Base64 decoding failed
            break;
        case 'Unsupported alg':
            // Algorithm is not HS256
            break;
        case 'Invalid signature':
            // Signature verification failed
            break;
        case 'Token expired':
            // Token has expired
            break;
        default:
            // Other error
            break;
    }
}
```

### Discount Calculation Errors
```php
try {
    $discount = calculateProductDiscount($productId, $subcategoryId, $categoryId);
    $finalPrice = applyDiscount($originalPrice, $discount);
} catch (PDOException $e) {
    // Database error
    error_log("Discount calculation error: " . $e->getMessage());
    $finalPrice = $originalPrice; // Fallback to original price
}
```

## Dependencies

### JWT Utilities
- PHP 7.4+ (for typed parameters)
- `json_encode()` and `json_decode()` functions
- `hash_hmac()` function
- `hash_equals()` function (timing attack protection)

### Discount Calculator
- `config/db.php`: Database connection
- Database table: `discounts`
- PDO extension

## Database Schema (for Discount Calculator)

```sql
CREATE TABLE discounts (
    id INT PRIMARY KEY AUTO_INCREMENT,
    name VARCHAR(255) NOT NULL,
    discount_type ENUM('percentage', 'fixed') NOT NULL,
    discount_value DECIMAL(10,2) NOT NULL,
    target_type ENUM('product', 'subcategory', 'category') NOT NULL,
    target_id INT NOT NULL,
    priority INT DEFAULT 0,
    is_active TINYINT(1) DEFAULT 1,
    start_date DATETIME NOT NULL,
    end_date DATETIME NULL,
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    updated_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
    
    INDEX idx_discounts_target (target_type, target_id),
    INDEX idx_discounts_active (is_active),
    INDEX idx_discounts_dates (start_date, end_date)
);
```

## Performance Considerations

### JWT Utilities
- **Lightweight**: No external dependencies
- **Fast encoding/decoding**: Pure PHP implementation
- **Memory efficient**: Minimal memory footprint

### Discount Calculator
- **Optimized queries**: Uses indexes for fast lookups
- **Priority sorting**: Database-level sorting for efficiency
- **Single query**: Gets best discount in one database call

## Security Best Practices

### JWT Security
1. **Use strong secrets**: Minimum 256-bit random keys
2. **Rotate secrets regularly**: Implement key rotation
3. **Short token lifetimes**: Use refresh tokens for long sessions
4. **Validate on every request**: Don't cache decoded tokens
5. **Use HTTPS**: Protect tokens in transit

### Discount Security
1. **Validate inputs**: Sanitize product/category IDs
2. **Check permissions**: Ensure user can access pricing
3. **Audit discount changes**: Log discount applications
4. **Rate limiting**: Prevent discount calculation abuse

## Common Use Cases

### Authentication Flow
1. User login → Generate JWT tokens
2. API requests → Validate JWT tokens
3. Token refresh → Generate new access token
4. User logout → Client discards tokens

### Pricing Flow
1. Product request → Calculate applicable discounts
2. Apply best discount → Return final price
3. Cart calculation → Apply discounts to all items
4. Checkout → Final price validation

## Notes

- JWT tokens are stateless and self-contained
- Discount calculations consider hierarchy (product > subcategory > category)
- All utilities are designed for high-performance API usage
- Error handling follows consistent patterns across the API
- Functions are designed to be easily testable and maintainable