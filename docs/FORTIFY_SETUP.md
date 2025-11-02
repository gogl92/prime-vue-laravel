# Laravel Fortify Backend Setup

## Overview
Laravel Fortify has been successfully installed and configured for backend-only authentication with Two-Factor Authentication (2FA) support. No frontend views are included - this is API-ready.

## Installation Details

### Packages Installed
- `laravel/fortify` (v1.31.2)
- `pragmarx/google2fa` (v8.0.3) - Google2FA library for 2FA
- `bacon/bacon-qr-code` (v3.0.1) - QR code generation
- `paragonie/constant_time_encoding` (v3.1.3) - Security helpers

### Configuration Files Created
- `config/fortify.php` - Main Fortify configuration
- `app/Providers/FortifyServiceProvider.php` - Service provider for Fortify
- `app/Actions/Fortify/CreateNewUser.php` - User registration action
- `app/Actions/Fortify/ResetUserPassword.php` - Password reset action
- `app/Actions/Fortify/UpdateUserPassword.php` - Password update action
- `app/Actions/Fortify/UpdateUserProfileInformation.php` - Profile update action
- `app/Actions/Fortify/PasswordValidationRules.php` - Password validation rules trait

### Database Changes
Migration: `2025_11_02_182000_add_two_factor_columns_to_users_table.php`

Added columns to `users` table:
- `two_factor_secret` (text, nullable) - Stores encrypted 2FA secret
- `two_factor_recovery_codes` (text, nullable) - Stores encrypted recovery codes
- `two_factor_confirmed_at` (timestamp, nullable) - Tracks when 2FA was confirmed

## Model Configuration

### User Model Updates
The `User` model now:
- Implements `MustVerifyEmail` interface for email verification
- Uses `TwoFactorAuthenticatable` trait for 2FA functionality
- Hides 2FA secrets in API responses (`two_factor_secret`, `two_factor_recovery_codes`)

## Features Enabled

### In `config/fortify.php`:
- ✅ **Registration** - User registration
- ✅ **Reset Passwords** - Password reset functionality
- ✅ **Email Verification** - Email verification requirement
- ✅ **Update Profile Information** - Profile updates
- ✅ **Update Passwords** - Password changes
- ✅ **Two-Factor Authentication** - 2FA with confirmation required

### Views Disabled
`'views' => false` - No view routes registered, API-only mode

## Authentication Routes

### Registration & Login
```
POST   /register              - Register new user
POST   /login                 - Login user
POST   /logout                - Logout user
```

### Password Management
```
POST   /forgot-password       - Request password reset link
POST   /reset-password        - Reset password with token
PUT    /user/password         - Update password (authenticated)
```

### Email Verification
```
POST   /email/verification-notification  - Resend verification email
GET    /verify-email/{id}/{hash}        - Verify email address
```

### Profile Management
```
PUT    /user/profile-information        - Update profile information
```

### Two-Factor Authentication (2FA)

#### Enable 2FA
```
POST   /user/two-factor-authentication
```
Enables 2FA for the authenticated user. After enabling, the user must confirm it.

#### Get QR Code
```
GET    /user/two-factor-qr-code
```
Returns SVG QR code for scanning with authenticator apps (Google Authenticator, Authy, etc.)

Response format:
```json
{
  "svg": "<svg>...</svg>"
}
```

#### Get Secret Key
```
GET    /user/two-factor-secret-key
```
Returns the plain text secret key for manual entry into authenticator apps.

Response format:
```json
{
  "secretKey": "ABCD1234..."
}
```

#### Confirm 2FA Setup
```
POST   /user/confirmed-two-factor-authentication
Body: { "code": "123456" }
```
Confirms 2FA setup by verifying a code from the authenticator app.

#### Get Recovery Codes
```
GET    /user/two-factor-recovery-codes
```
Returns the recovery codes (only after 2FA is confirmed).

Response format:
```json
[
  "code1",
  "code2",
  "code3",
  ...
]
```

#### Regenerate Recovery Codes
```
POST   /user/two-factor-recovery-codes
```
Generates new recovery codes (invalidates old ones).

#### Disable 2FA
```
DELETE /user/two-factor-authentication
```
Disables 2FA for the authenticated user.

#### Two-Factor Challenge
```
POST   /two-factor-challenge
Body: { "code": "123456" } or { "recovery_code": "abc-def-ghi" }
```
Used during login when user has 2FA enabled. Submit either:
- `code` - 6-digit code from authenticator app
- `recovery_code` - One of the recovery codes

## Custom Authentication

The `FortifyServiceProvider` includes custom authentication logic:

```php
Fortify::authenticateUsing(function (Request $request) {
    $user = User::where('email', $request->email)->first();
    
    if ($user && Hash::check($request->password, $user->password)) {
        return $user;
    }
});
```

This authenticates users by email and password.

## Rate Limiting

### Login Attempts
- 5 attempts per minute per email/IP combination
- Throttle key: `{email}|{ip}`

### Two-Factor Attempts
- 5 attempts per minute per session
- Throttle key: session's `login.id`

## Configuration Options

### Fortify Guard
```php
'guard' => 'web'
```
Uses the default web guard for authentication.

### Username Field
```php
'username' => 'email'
```
Users log in with their email address.

### Home Path
```php
'home' => '/home'
```
Redirect path after successful authentication.

### Middleware
```php
'middleware' => ['web']
```
All Fortify routes use the web middleware.

## 2FA Implementation Flow

### For Users Enabling 2FA:
1. **POST** `/user/two-factor-authentication` - Enable 2FA (creates secret)
2. **GET** `/user/two-factor-qr-code` - Display QR code to user
3. User scans QR code with authenticator app
4. **POST** `/user/confirmed-two-factor-authentication` with code - Confirm setup
5. **GET** `/user/two-factor-recovery-codes` - Show recovery codes to user

### For Users Logging In with 2FA:
1. **POST** `/login` with email/password
2. If 2FA is enabled, server responds with redirect to 2FA challenge
3. **POST** `/two-factor-challenge` with code or recovery code
4. User is authenticated and logged in

### For Users Disabling 2FA:
1. **DELETE** `/user/two-factor-authentication` - Disable 2FA

## Security Features

1. **Password Confirmation**: Critical actions require password confirmation
2. **Email Verification**: New users must verify their email
3. **Rate Limiting**: Prevents brute force attacks
4. **Recovery Codes**: Backup access if user loses authenticator device
5. **Encrypted Secrets**: 2FA secrets are encrypted in database
6. **2FA Confirmation**: Users must confirm 2FA setup with a valid code

## Testing the Setup

### Test Registration
```bash
curl -X POST http://localhost/register \
  -H "Content-Type: application/json" \
  -d '{"name":"Test User","email":"test@example.com","password":"password","password_confirmation":"password"}'
```

### Test Login
```bash
curl -X POST http://localhost/login \
  -H "Content-Type: application/json" \
  -d '{"email":"test@example.com","password":"password"}'
```

### Test Enable 2FA (requires authentication)
```bash
curl -X POST http://localhost/user/two-factor-authentication \
  -H "Authorization: Bearer {token}"
```

### Test Get QR Code
```bash
curl -X GET http://localhost/user/two-factor-qr-code \
  -H "Authorization: Bearer {token}"
```

## Next Steps (Frontend Integration)

When you're ready to add frontend support:

1. Create Vue/React components for:
   - Login form with 2FA challenge
   - 2FA setup wizard (QR code display, confirmation)
   - Recovery codes display
   - 2FA management settings

2. Handle API responses and redirects properly

3. Store and display QR codes securely

4. Implement password confirmation before critical actions

## References

- [Laravel Fortify Documentation](https://laravel.com/docs/12.x/fortify)
- [Laravel Authentication](https://laravel.com/docs/12.x/authentication)
- [Laravel Sanctum](https://laravel.com/docs/12.x/sanctum) - Already configured for API tokens

## Notes

- Fortify works alongside your existing authentication setup
- The `routes/auth.php` file contains additional auth routes from Laravel Breeze/starter kit
- Both Fortify and existing auth routes are active
- Sanctum is used for API token authentication
- 2FA uses Google Authenticator compatible TOTP (Time-based One-Time Password)

