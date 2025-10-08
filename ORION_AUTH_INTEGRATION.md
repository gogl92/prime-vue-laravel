# Orion API Authentication Integration

This document explains how Laravel Sanctum authentication tokens are integrated with Laravel Orion API requests in this application.

## Overview

The application uses **Laravel Sanctum** tokens to authenticate Orion API requests independently from Inertia's session-based authentication. Upon login or registration, a Sanctum token is generated and stored in `localStorage` as `auth_token`, which is then used for all Orion API requests.

## Architecture

### Backend Components

#### 1. **Authentication Controllers** (`app/Http/Controllers/Auth/`)
- **`AuthenticatedSessionController.php`**: Generates Sanctum token on login
- **`RegisteredUserController.php`**: Generates Sanctum token on registration
- Both controllers store the token in the session temporarily to be shared with the frontend

#### 2. **Inertia Middleware** (`app/Http/Middleware/HandleInertiaRequests.php`)
- Shares the `auth_token` from session with Inertia props
- Uses `pull()` to retrieve and remove the token after first share (security measure)

#### 3. **API Routes** (`routes/api.php`)
- Orion routes are protected with `auth:sanctum` middleware
- Sanctum validates the bearer token from API requests

#### 4. **Orion Config** (`config/orion.php`)
- Auth guard set to `api` (which uses Sanctum)

#### 5. **Auth Config** (`config/auth.php`)
- API guard uses Sanctum driver

### Frontend Components

#### 1. **Orion Service** (`resources/js/services/orion.ts`)
- Initializes Orion with base URL
- Manages authentication token
- Provides methods:
  - `setAuthToken(token)`: Sets token for Orion requests
  - `clearAuth()`: Clears token from Orion and localStorage
  - `setupAuth()`: Loads token from localStorage on init

#### 2. **Auth Token Composable** (`resources/js/composables/useAuthToken.ts`)
- Watches for auth token in Inertia props
- Automatically stores token in localStorage when received
- Updates Orion service with the new token

#### 3. **App Layout Composable** (`resources/js/composables/useAppLayout.ts`)
- Logout function clears token from localStorage and Orion service
- Ensures clean logout state

#### 4. **Main App** (`resources/js/app.ts`)
- Initializes Orion service on startup
- Calls `useAuthToken()` composable to watch for tokens

#### 5. **TypeScript Types** (`resources/js/types/index.d.ts`)
- Extends `AuthProps` interface to include optional `token` field

## Authentication Flow

### Login/Registration Flow

```
1. User submits login/registration form
   ↓
2. Backend authenticates user (Laravel Breeze)
   ↓
3. Backend generates Sanctum token
   ↓
4. Token stored in session (temporary)
   ↓
5. Redirect to dashboard
   ↓
6. Inertia middleware shares token via props (then removes from session)
   ↓
7. Frontend useAuthToken() composable detects token
   ↓
8. Token stored in localStorage as 'auth_token'
   ↓
9. Orion service updated with token
   ↓
10. All Orion API requests now include Bearer token
```

### Logout Flow

```
1. User clicks logout
   ↓
2. Frontend clears token from localStorage
   ↓
3. Frontend clears Orion service token
   ↓
4. Backend revokes all user tokens
   ↓
5. Backend performs standard session logout
```

### Page Load Flow (After Login)

```
1. App initializes
   ↓
2. Orion service constructor runs
   ↓
3. setupAuth() checks localStorage for 'auth_token'
   ↓
4. If found, sets token in Orion
   ↓
5. All subsequent Orion requests authenticated
```

## Security Considerations

1. **Token Storage**: Tokens stored in localStorage (XSS vulnerable but necessary for API calls)
2. **Session Pull**: Token removed from session after first share to prevent exposure
3. **Token Revocation**: All tokens revoked on logout
4. **HTTPS**: Always use HTTPS in production to protect tokens in transit
5. **Sanctum Protection**: API routes protected with `auth:sanctum` middleware
6. **Controller Authorization**: InvoiceController checks `auth()->check()` for all operations

## API Request Example

When making Orion requests, the token is automatically included:

```typescript
// Example from InvoicesExample.vue
const invoices = await Invoice.$query()
    .filter('city', FilterOperator.Equal, 'New York')
    .get()

// Behind the scenes:
// Request headers include: Authorization: Bearer {token}
```

## Testing Authentication

### 1. **Login and Check Token**
```javascript
// After login, check browser console:
localStorage.getItem('auth_token') // Should return token string
```

### 2. **Monitor Network Requests**
- Open DevTools > Network tab
- Navigate to Invoices page
- Check API requests to `/api/invoices`
- Verify `Authorization: Bearer {token}` header is present

### 3. **Test Logout**
```javascript
// After logout, check:
localStorage.getItem('auth_token') // Should return null
```

## Troubleshooting

### Problem: API requests return 401 Unauthorized

**Possible causes:**
1. Token not stored in localStorage
2. Token expired or invalid
3. Sanctum not configured correctly
4. CORS issues

**Solutions:**
1. Check `localStorage.getItem('auth_token')`
2. Re-login to get fresh token
3. Verify `config/sanctum.php` settings
4. Check `config/cors.php` for allowed origins

### Problem: Token not stored after login

**Check:**
1. Browser console for errors
2. Inertia props contain token: `$page.props.auth.token`
3. `useAuthToken()` composable is called in app.ts

### Problem: Token persists after logout

**Check:**
1. `useAppLayout` logout function calls `clearAuth()`
2. Backend revokes tokens in `AuthenticatedSessionController::destroy()`

## Related Files

**Backend:**
- `app/Http/Controllers/Auth/AuthenticatedSessionController.php`
- `app/Http/Controllers/Auth/RegisteredUserController.php`
- `app/Http/Middleware/HandleInertiaRequests.php`
- `app/Http/Controllers/InvoiceController.php`
- `app/Models/User.php` (HasApiTokens trait)
- `routes/api.php`
- `config/auth.php`
- `config/orion.php`
- `config/sanctum.php`

**Frontend:**
- `resources/js/app.ts`
- `resources/js/services/orion.ts`
- `resources/js/composables/useAuthToken.ts`
- `resources/js/composables/useAppLayout.ts`
- `resources/js/types/index.d.ts`
- `resources/js/pages/InvoicesExample.vue`
- `resources/js/models/Invoice.ts`

## Additional Notes

- The authentication mechanism is transparent to components using Orion
- Tokens are scoped to the authenticated user
- Multiple tabs/windows share the same token (localStorage is domain-wide)
- Token refresh is not implemented (requires re-login when expired)
- Consider implementing token refresh logic for production apps

