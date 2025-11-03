# 🔐 How to Enable and Test Two-Factor Authentication (2FA)

## Prerequisites

1. ✅ Email is working (Mailpit at http://localhost:8025)
2. ✅ You have a user account registered
3. ✅ Your account email is verified
4. 📱 **Download an authenticator app**:
   - Google Authenticator (iOS/Android)
   - Authy (iOS/Android/Desktop)
   - Microsoft Authenticator (iOS/Android)
   - Any TOTP-compatible app

## Step-by-Step Guide to Enable 2FA

### 1. Login to Your Application
```
Navigate to: http://localhost/login
```

### 2. Navigate to Password Settings
After logging in, go to:
```
http://localhost/settings/password
```

Or use the navigation menu:
- Click your profile/settings
- Go to **Password** section

### 3. Enable Two-Factor Authentication

You'll see a section titled **"Two-Factor Authentication"**

1. Click the **"Enable Two-Factor Authentication"** button
2. Wait a moment while it generates your 2FA secret

### 4. Scan the QR Code

A QR code will appear on your screen along with a setup key.

**Using your phone:**
1. Open your authenticator app
2. Tap "Add account" or "+" 
3. Choose "Scan QR code"
4. Point your camera at the QR code on the screen
5. The account will be added automatically

**Manual entry (if QR scanning doesn't work):**
1. In your authenticator app, choose "Enter a setup key"
2. Account name: Your app name (e.g., "Laravel App")
3. Your email: The email you used to register
4. Key: Copy the "Setup Key" displayed below the QR code
5. Type of key: Time-based
6. Tap "Add"

### 5. Confirm 2FA Setup

1. Your authenticator app will now show a 6-digit code that changes every 30 seconds
2. Enter the current 6-digit code in the **"Authentication Code"** field
3. Click **"Confirm"**

### 6. Save Your Recovery Codes

⚠️ **VERY IMPORTANT!**

After confirming, you'll see **10 recovery codes** displayed.

**Save these codes securely:**
- Copy them to a password manager
- Print them and store in a safe place
- Screenshot and save securely
- **DO NOT LOSE THESE!**

These codes are your backup if you lose your phone or authenticator app.

Each recovery code can only be used **once**.

### 7. Success! 2FA is Enabled

You'll see a success message and the 2FA section will show:
- ✅ "You have enabled two-factor authentication"
- Your recovery codes (blurred for security)
- Option to regenerate recovery codes
- Option to disable 2FA

## Testing Your 2FA

### Test Login with 2FA

1. **Logout** of your application
2. Go to login page: http://localhost/login
3. Enter your **email** and **password**
4. Click **"Log in"**
5. 🔐 You'll be redirected to the **Two-Factor Challenge** page
6. Open your **authenticator app**
7. Enter the current **6-digit code**
8. Click **"Submit"**
9. ✅ You're logged in!

### Test Recovery Code

1. **Logout** again
2. Go to login page and enter credentials
3. On the 2FA challenge page, click **"Use a recovery code"**
4. Enter one of your saved recovery codes
5. Click **"Submit"**
6. ✅ You're logged in!
7. ⚠️ Note: That recovery code is now consumed and cannot be reused

## Managing Your 2FA

### Regenerate Recovery Codes

If you've used some recovery codes or want fresh ones:

1. Go to: http://localhost/settings/password
2. Scroll to **Two-Factor Authentication** section
3. Click **"Regenerate Recovery Codes"**
4. ⚠️ **Old codes are now invalid!**
5. Save the new codes securely

### Disable 2FA

If you want to turn off 2FA:

1. Go to: http://localhost/settings/password
2. Scroll to **Two-Factor Authentication** section
3. Click **"Disable Two-Factor Authentication"** (red button)
4. Confirm in the dialog
5. ✅ 2FA is now disabled

## Troubleshooting

### "Invalid authentication code" Error

**Possible causes:**
1. **Time sync issue** - Your phone and server times are out of sync
   - Solution: Ensure your phone's time is set to automatic
   - Check: http://time.is to verify your time is correct

2. **Code expired** - Codes change every 30 seconds
   - Solution: Wait for a new code to appear, then enter it

3. **Wrong app/account** - You scanned the wrong QR code
   - Solution: Remove the account from your authenticator and re-add it

### QR Code Not Displaying

1. Check browser console for errors
2. Refresh the page
3. Try clicking "Enable 2FA" again
4. Use manual entry with the setup key instead

### Can't Access Account (Lost Phone)

**If you have recovery codes:**
1. Login with email/password
2. Click "Use a recovery code"
3. Enter one of your saved recovery codes

**If you don't have recovery codes:**
- You'll need database access to disable 2FA:
```bash
./vendor/bin/sail artisan tinker

# In tinker:
$user = App\Models\User::where('email', 'your@email.com')->first();
$user->two_factor_secret = null;
$user->two_factor_recovery_codes = null;
$user->two_factor_confirmed_at = null;
$user->save();
```

### Recovery Codes Not Showing

Recovery codes only show:
1. Right after you first enable 2FA
2. After you regenerate them

They're not stored for later viewing (security best practice).

If you lost them, you must regenerate new ones while logged in.

## Security Best Practices

✅ **DO:**
- Keep recovery codes in a secure password manager
- Use a reputable authenticator app
- Enable 2FA on important accounts
- Regenerate codes if you think they're compromised

❌ **DON'T:**
- Share your QR code or setup key
- Share recovery codes
- Screenshot and leave on your phone unprotected
- Email recovery codes to yourself

## Technical Details

### What Happens Behind the Scenes

1. **Enable 2FA**: Generates a secret key stored encrypted in database
2. **QR Code**: Encodes your secret key in a scannable format
3. **Authenticator App**: Uses TOTP (Time-based One-Time Password) algorithm
4. **6-digit codes**: Generated from secret + current time (30s intervals)
5. **Recovery codes**: Random codes stored hashed in database

### Database Changes

When 2FA is enabled, three columns are populated:
- `two_factor_secret` - Encrypted secret key
- `two_factor_recovery_codes` - Encrypted recovery codes
- `two_factor_confirmed_at` - Timestamp of confirmation

## Quick Reference URLs

| Purpose | URL |
|---------|-----|
| Login | http://localhost/login |
| Password Settings (2FA) | http://localhost/settings/password |
| Mailpit (emails) | http://localhost:8025 |

## Need Help?

Check the documentation:
- `docs/FORTIFY_SETUP.md` - Backend implementation
- `docs/FRONTEND_2FA_IMPLEMENTATION.md` - Frontend details
- `FORTIFY_IMPLEMENTATION_COMPLETE.md` - Quick overview

## Summary

✅ 2FA adds an extra layer of security to your account  
✅ Requires something you know (password) + something you have (phone)  
✅ Recovery codes provide backup access  
✅ Takes ~2 minutes to set up  
✅ Compatible with all major authenticator apps  

**Now go ahead and test it! 🔐**

