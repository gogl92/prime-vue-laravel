# Email Configuration for Mailpit

## Current Issue
Your `.env` file has mail configured to use `log` driver, which writes emails to log files instead of sending them to Mailpit.

## Solution

Update the following lines in your `.env` file:

```env
# Change from:
MAIL_MAILER=log
MAIL_HOST=127.0.0.1
MAIL_PORT=2525

# To:
MAIL_MAILER=smtp
MAIL_HOST=mailpit
MAIL_PORT=1025
MAIL_USERNAME=null
MAIL_PASSWORD=null
MAIL_ENCRYPTION=null
```

## Full Mail Configuration

Your `.env` should have these mail settings:

```env
MAIL_MAILER=smtp
MAIL_HOST=mailpit
MAIL_PORT=1025
MAIL_USERNAME=null
MAIL_PASSWORD=null
MAIL_ENCRYPTION=null
MAIL_FROM_ADDRESS="hello@example.com"
MAIL_FROM_NAME="${APP_NAME}"
```

## After Updating

1. **Clear config cache**:
```bash
./vendor/bin/sail artisan config:clear
```

2. **Test email sending**:
```bash
./vendor/bin/sail artisan tinker
```

Then in tinker:
```php
Mail::raw('Test email', function($message) {
    $message->to('test@example.com')
            ->subject('Test Email from Laravel');
});
```

3. **Check Mailpit**:
   - Open browser to: http://localhost:8025
   - You should see the test email!

## Mailpit Ports

From your docker-compose.yml:
- **SMTP Port**: 1025 (for sending emails)
- **Web UI Port**: 8025 (for viewing emails in browser)

## Testing Email Verification

After updating your `.env`:

1. Register a new user
2. Check Mailpit at http://localhost:8025
3. You should see the verification email
4. Click the verification link in the email

## Important Note

**You must use the Docker service name `mailpit`** as the `MAIL_HOST` when running inside Docker/Sail. 

Using `127.0.0.1` or `localhost` won't work because that refers to the container itself, not the host machine or other containers.

