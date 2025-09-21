# Authentication API Documentation

## Overview
The Authentication API provides JWT-based authentication for the photography store system. It handles user login, token refresh, logout, and user profile retrieval with role-based access control.

## Base URL
```
http://localhost/api/auth/
```

## Authentication Flow
This API uses JWT (JSON Web Tokens) for stateless authentication:
1. **Login** - Exchange credentials for access and refresh tokens
2. **Access Token** - Short-lived token for API requests (default: 1 hour)
3. **Refresh Token** - Long-lived token for getting new access tokens (default: 7 days)
4. **Token Refresh** - Exchange refresh token for new access token
5. **Logout** - Client-side token disposal (stateless)

## Environment Configuration
Required environment variables:
```bash
JWT_SECRET=your-secret-key-here
JWT_TTL=3600          # Access token TTL in seconds (1 hour)
REFRESH_TTL=604800    # Refresh token TTL in seconds (7 days)
```

## Endpoints

### 1. Login
**Endpoint:** `POST /login.php`
**Authentication:** None required

#### Request Body
```json
{
  "email": "john.smith@photostore.com",
  "password": "password123"
}
```

#### Request Parameters
- `email` (string, required): User's email address
- `password` (string, required): User's password

#### Response Format (Success)
```json
{
  "success": true,
  "user": {
    "id": 1,
    "full_name": "John Smith",
    "email": "john.smith@photostore.com",
    "phone": "+1234567890",
    "role": "admin",
    "is_active": 1,
    "last_login_at": "2024-01-15 14:30:00",
    "created_at": "2024-01-01 09:00:00",
    "updated_at": "2024-01-15 14:30:00"
  },
  "access_token": "eyJ0eXAiOiJKV1QiLCJhbGciOiJIUzI1NiJ9...",
  "expires_in": 3600,
  "refresh_token": "eyJ0eXAiOiJKV1QiLCJhbGciOiJIUzI1NiJ9...",
  "refresh_expires_in": 604800
}
```

#### Token Payload Structure
**Access Token:**
```json
{
  "uid": 1,
  "role": "admin",
  "typ": "at",
  "iat": 1642248000,
  "exp": 1642251600
}
```

**Refresh Token:**
```json
{
  "uid": 1,
  "role": "admin",
  "typ": "rt",
  "iat": 1642248000,
  "exp": 1642852800
}
```

#### Features
- Password verification using `password_verify()`
- Account status validation (must be active)
- Last login timestamp update
- Dual token generation (access + refresh)
- User data sanitization (password_hash excluded)

### 2. Token Refresh
**Endpoint:** `POST /refresh.php`
**Authentication:** Valid refresh token required

#### Request Body
```json
{
  "refresh_token": "eyJ0eXAiOiJKV1QiLCJhbGciOiJIUzI1NiJ9..."
}
```

#### Request Parameters
- `refresh_token` (string, required): Valid refresh token from login

#### Response Format (Success)
```json
{
  "success": true,
  "access_token": "eyJ0eXAiOiJKV1QiLCJhbGciOiJIUzI1NiJ9...",
  "expires_in": 3600
}
```

#### Features
- Refresh token validation and type checking
- User account status re-verification
- New access token generation
- Maintains user role and permissions

### 3. Get Current User
**Endpoint:** `GET /me.php`
**Authentication:** Valid access token required

#### Headers
```
Authorization: Bearer eyJ0eXAiOiJKV1QiLCJhbGciOiJIUzI1NiJ9...
```

#### Response Format
```json
{
  "success": true,
  "user": {
    "id": 1,
    "full_name": "John Smith",
    "email": "john.smith@photostore.com",
    "phone": "+1234567890",
    "role": "admin",
    "is_active": 1,
    "last_login_at": "2024-01-15 14:30:00",
    "created_at": "2024-01-01 09:00:00",
    "updated_at": "2024-01-15 14:30:00"
  }
}
```

#### Features
- Token validation via `require_auth()` middleware
- Current user profile retrieval
- Real-time user data (fresh from database)

### 4. Logout
**Endpoint:** `POST /logout.php`
**Authentication:** None required (stateless)

#### Response Format
```json
{
  "success": true,
  "message": "Logged out (client should discard tokens)"
}
```

#### Features
- Stateless logout (JWT tokens remain valid until expiry)
- Client-side token disposal instruction
- No server-side token blacklisting (by design)

**Note:** This is a stateless JWT implementation. For true token invalidation, implement a token blacklist system.

## Error Codes
- `400` - Bad Request (missing email/password, missing refresh_token)
- `401` - Unauthorized (invalid credentials, invalid/expired tokens, inactive account)
- `405` - Method Not Allowed
- `500` - Internal Server Error (database errors, JWT processing errors)

## Error Response Format
```json
{
  "error": "Invalid credentials"
}
```

## Security Features

### Password Security
- Secure password hashing using `password_hash()`
- Password verification using `password_verify()`
- No password storage in plain text
- No password exposure in API responses

### JWT Security
- HMAC-SHA256 signature algorithm
- Configurable secret key via environment variables
- Token type validation (access vs refresh)
- Expiration time enforcement
- User ID and role embedding

### Account Security
- Active account validation on login
- Account status re-check on token refresh
- Last login timestamp tracking
- Role-based access control integration

### API Security
- CORS configuration
- Content-Type validation
- Input sanitization
- Method validation
- Error message standardization

## Token Management

### Access Token
- **Purpose:** API request authentication
- **Lifetime:** 1 hour (configurable)
- **Usage:** Include in Authorization header
- **Format:** `Authorization: Bearer <access_token>`

### Refresh Token
- **Purpose:** Access token renewal
- **Lifetime:** 7 days (configurable)
- **Usage:** Send to refresh endpoint
- **Security:** Should be stored securely (httpOnly cookie recommended)

### Token Storage Recommendations
- **Access Token:** Memory or sessionStorage (short-lived)
- **Refresh Token:** httpOnly cookie or secure storage
- **Never:** Store tokens in localStorage for production

## Integration Examples

### Frontend Authentication Flow
```javascript
// 1. Login
const loginResponse = await fetch('/api/auth/login.php', {
  method: 'POST',
  headers: { 'Content-Type': 'application/json' },
  body: JSON.stringify({ email, password })
});
const { access_token, refresh_token, user } = await loginResponse.json();

// 2. Store tokens securely
localStorage.setItem('access_token', access_token);
// Store refresh_token in httpOnly cookie (server-side)

// 3. Use access token for API requests
const apiResponse = await fetch('/api/products/get.php', {
  headers: { 'Authorization': `Bearer ${access_token}` }
});

// 4. Refresh token when access token expires
const refreshResponse = await fetch('/api/auth/refresh.php', {
  method: 'POST',
  headers: { 'Content-Type': 'application/json' },
  body: JSON.stringify({ refresh_token })
});
const { access_token: newToken } = await refreshResponse.json();
```

### Automatic Token Refresh
```javascript
// Axios interceptor example
axios.interceptors.response.use(
  response => response,
  async error => {
    if (error.response?.status === 401) {
      try {
        const refreshResponse = await axios.post('/api/auth/refresh.php', {
          refresh_token: getRefreshToken()
        });
        const { access_token } = refreshResponse.data;
        setAccessToken(access_token);
        // Retry original request
        error.config.headers.Authorization = `Bearer ${access_token}`;
        return axios.request(error.config);
      } catch (refreshError) {
        // Redirect to login
        window.location.href = '/login';
      }
    }
    return Promise.reject(error);
  }
);
```

## cURL Examples

### Login
```bash
curl -X POST "http://localhost/api/auth/login.php" \
  -H "Content-Type: application/json" \
  -d '{
    "email": "john.smith@photostore.com",
    "password": "password123"
  }'
```

### Get Current User
```bash
curl -X GET "http://localhost/api/auth/me.php" \
  -H "Authorization: Bearer eyJ0eXAiOiJKV1QiLCJhbGciOiJIUzI1NiJ9..."
```

### Refresh Token
```bash
curl -X POST "http://localhost/api/auth/refresh.php" \
  -H "Content-Type: application/json" \
  -d '{
    "refresh_token": "eyJ0eXAiOiJKV1QiLCJhbGciOiJIUzI1NiJ9..."
  }'
```

### Logout
```bash
curl -X POST "http://localhost/api/auth/logout.php"
```

## Common Use Cases

### User Authentication Workflow
1. **Initial Login:** User provides credentials
2. **Token Storage:** Store access and refresh tokens securely
3. **API Requests:** Include access token in Authorization header
4. **Token Expiry:** Automatically refresh when access token expires
5. **Session Management:** Handle logout and token cleanup

### Role-Based Access
1. **Login:** Receive user role in token payload
2. **Authorization:** Middleware validates role for protected endpoints
3. **Permission Checks:** Frontend can check user.role for UI decisions
4. **Role Updates:** Re-login required for role changes

### Security Best Practices
1. **HTTPS Only:** Always use HTTPS in production
2. **Secure Storage:** Use httpOnly cookies for refresh tokens
3. **Token Rotation:** Implement refresh token rotation
4. **Logout Cleanup:** Clear all tokens on logout
5. **Error Handling:** Don't expose sensitive information in errors

## Dependencies

### Required Files
- `../config/cors.php` - CORS configuration
- `../config/db.php` - Database connection
- `../utils/jwt.php` - JWT utility functions
- `../middleware/require_auth.php` - Authentication middleware

### Database Tables
- `users` - User account information

### Environment Variables
- `JWT_SECRET` - Secret key for JWT signing
- `JWT_TTL` - Access token lifetime
- `REFRESH_TTL` - Refresh token lifetime

## Notes

1. **Stateless Design:** No server-side session storage
2. **JWT Expiry:** Tokens expire automatically, no manual invalidation
3. **Account Status:** Inactive accounts cannot login or refresh tokens
4. **Role Persistence:** User role is embedded in tokens
5. **Login Tracking:** Last login timestamp is updated on successful login
6. **Error Consistency:** All endpoints return consistent error formats
7. **CORS Support:** Configured for cross-origin requests
8. **Content Type:** All endpoints expect and return JSON
9. **Method Validation:** Only specified HTTP methods are allowed
10. **Security Headers:** Proper content-type headers are set

## Troubleshooting

### Common Issues
- **Invalid JWT Secret:** Ensure JWT_SECRET environment variable is set
- **Token Expiry:** Check token timestamps and TTL configuration
- **Inactive Account:** Verify user.is_active = 1 in database
- **Wrong Token Type:** Ensure access tokens for API, refresh tokens for refresh
- **CORS Errors:** Check CORS configuration for frontend domain

### Debug Tips
- Check server logs for detailed error messages
- Verify JWT payload using online JWT decoders
- Test with cURL to isolate frontend issues
- Monitor database for login timestamp updates
- Validate environment variable configuration