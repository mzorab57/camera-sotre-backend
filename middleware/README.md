# API Middleware Documentation

This directory contains authentication and authorization middleware components for the API system. These middleware functions provide security layers for protecting endpoints and validating user permissions.

## Overview

The middleware system consists of four main components:
- **Authentication**: Token validation and user verification
- **Role-based Authorization**: Permission checking based on user roles
- **Convenience Wrappers**: Pre-configured middleware for common use cases

## Base URL
```
/api/middleware/
```

## Middleware Components

### 1. Authentication Middleware (`require_auth.php`)

Core authentication middleware that validates JWT tokens and loads user data.

#### Functions

##### `get_authorization_token(): ?string`
Extracts Bearer token from Authorization header.

**Returns:**
- `string`: The JWT token if found
- `null`: If no valid token is present

##### `require_auth(): array`
Validates JWT token and loads authenticated user data.

**Process:**
1. Extracts Bearer token from request headers
2. Validates JWT token using secret key
3. Fetches user from database using token payload
4. Verifies user is active
5. Stores user data in global variables

**Returns:**
- `array`: JWT payload data

**Errors:**
- `401`: Missing Authorization Bearer token
- `401`: Invalid token
- `401`: Account disabled or not found

##### `auth_user(): ?array`
Returns the currently authenticated user data.

**Returns:**
```json
{
  "id": 1,
  "full_name": "John Doe",
  "email": "john@example.com",
  "phone": "+1234567890",
  "role": "admin",
  "is_active": 1,
  "last_login_at": "2024-01-15 10:30:00",
  "created_at": "2024-01-01 00:00:00",
  "updated_at": "2024-01-15 10:30:00"
}
```

##### `auth_payload(): ?array`
Returns the JWT payload data.

**Returns:**
```json
{
  "uid": 1,
  "email": "john@example.com",
  "role": "admin",
  "iat": 1705312200,
  "exp": 1705315800
}
```

### 2. Role Authorization (`require_role.php`)

Role-based access control middleware.

#### Functions

##### `require_role(array $roles): void`
Validates that the authenticated user has one of the specified roles.

**Parameters:**
- `$roles` (array): List of allowed roles

**Process:**
1. Ensures user is authenticated
2. Checks if user's role is in the allowed roles list
3. Returns 403 if access is denied

**Example Usage:**
```php
require_role(['admin']); // Admin only
require_role(['admin', 'employee']); // Admin or Employee
```

**Errors:**
- `403`: Forbidden: insufficient role

### 3. Admin Protection (`protect_admin.php`)

Convenience middleware for admin-only endpoints.

**Usage:**
```php
require_once __DIR__ . '/middleware/protect_admin.php';
```

**Equivalent to:**
```php
require_auth();
require_role(['admin']);
```

### 4. Admin/Employee Protection (`protect_admin_employee.php`)

Convenience middleware for admin and employee endpoints.

**Usage:**
```php
require_once __DIR__ . '/middleware/protect_admin_employee.php';
```

**Equivalent to:**
```php
require_auth();
require_role(['admin', 'employee']);
```

## Usage Examples

### Basic Authentication
```php
<?php
require_once __DIR__ . '/middleware/require_auth.php';

// Require authentication
require_auth();

// Get current user
$user = auth_user();
echo "Hello, " . $user['full_name'];
```

### Role-Based Protection
```php
<?php
require_once __DIR__ . '/middleware/require_role.php';

// Require admin role
require_auth();
require_role(['admin']);

// Admin-only code here
```

### Using Convenience Middleware
```php
<?php
// For admin-only endpoints
require_once __DIR__ . '/middleware/protect_admin.php';

// For admin/employee endpoints
require_once __DIR__ . '/middleware/protect_admin_employee.php';
```

### Custom Role Validation
```php
<?php
require_once __DIR__ . '/middleware/require_auth.php';

// Custom validation logic
require_auth();
$user = auth_user();

if ($user['role'] !== 'admin' && $user['id'] !== $resource_owner_id) {
    http_response_code(403);
    echo json_encode(['error' => 'Access denied']);
    exit;
}
```

## Error Responses

### 401 Unauthorized
```json
{
  "error": "Missing Authorization Bearer token"
}
```

```json
{
  "error": "Invalid token"
}
```

```json
{
  "error": "Account disabled or not found"
}
```

### 403 Forbidden
```json
{
  "error": "Forbidden: insufficient role"
}
```

## Security Features

### JWT Token Validation
- Validates token signature using secret key
- Checks token expiration
- Verifies token structure and claims

### User Status Verification
- Ensures user account is active (`is_active = 1`)
- Validates user exists in database
- Loads fresh user data for each request

### Role-Based Access Control
- Supports multiple roles per endpoint
- Strict role matching (case-sensitive)
- Hierarchical access control through role combinations

### Header Flexibility
- Supports standard `Authorization` header
- Handles Apache redirect scenarios
- Fallback to `apache_request_headers()` if available

## Dependencies

- `config/cors.php`: CORS configuration
- `config/db.php`: Database connection
- `utils/jwt.php`: JWT encoding/decoding utilities
- Environment variable: `JWT_SECRET`

## Integration

These middleware components are used throughout the API:

- **Auth endpoints**: Use `require_auth()` for token refresh and user info
- **User management**: Use `protect_admin.php` for user CRUD operations
- **Content management**: Use `protect_admin_employee.php` for content operations
- **Public endpoints**: No middleware required

## Best Practices

1. **Always use HTTPS** in production to protect tokens in transit
2. **Include middleware early** in your endpoint files
3. **Use convenience wrappers** (`protect_admin.php`, `protect_admin_employee.php`) when possible
4. **Handle errors gracefully** and provide meaningful error messages
5. **Keep JWT secrets secure** and rotate them regularly
6. **Validate user status** on each request (handled automatically)

## Common Use Cases

### Public Endpoint
```php
<?php
require_once __DIR__ . '/config/cors.php';
// No authentication required
```

### User Profile Endpoint
```php
<?php
require_once __DIR__ . '/middleware/require_auth.php';
require_auth();
$user = auth_user();
```

### Admin Dashboard
```php
<?php
require_once __DIR__ . '/middleware/protect_admin.php';
// Admin-only functionality
```

### Content Management
```php
<?php
require_once __DIR__ . '/middleware/protect_admin_employee.php';
// Admin or employee functionality
```

### Resource Owner Check
```php
<?php
require_once __DIR__ . '/middleware/require_auth.php';
require_auth();
$user = auth_user();

// Allow admin or resource owner
if ($user['role'] !== 'admin' && $user['id'] !== $resource_owner_id) {
    http_response_code(403);
    echo json_encode(['error' => 'Access denied']);
    exit;
}
```

## Notes

- All middleware functions use `exit` to stop execution on authentication/authorization failures
- User data is cached in global variables for the duration of the request
- JWT tokens are stateless - user status is verified on each request
- The system supports role hierarchy through array-based role checking
- Middleware is designed to be lightweight and efficient for high-traffic APIs