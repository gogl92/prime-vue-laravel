# Branch Invoice Settings

This document explains how to configure and use branch-specific Mexican invoicing credentials.

## Overview

Each branch can now have its own invoicing credentials for both **Facturacom** and **Facturapi** services. This allows different branches to use different invoicing providers or different accounts with the same provider.

## Features

- **Per-branch configuration**: Each branch can have its own API credentials
- **Multiple providers**: Support for Facturacom and Facturapi
- **Fallback to defaults**: If a branch doesn't have custom credentials, it uses the global config
- **Secure storage**: Credentials are stored using the laravel-settings package
- **Easy-to-use UI**: Configure credentials through the web interface

## Configuration UI

### Accessing the Settings

You can access the branch invoice settings in two ways:

1. **Via Settings Menu**:
   - Go to Settings (click on your profile)
   - Click **"Branch Invoicing"** in the sidebar

2. **Direct URL**: Navigate to `/settings/branch-invoicing`

You'll see a list of all branches in your current company. Click **"Configure Invoicing"** on any branch to manage its credentials.

### Setting Up Credentials

For each branch, you can configure:

**Facturacom:**
- API URL (e.g., `https://factura.com/api`)
- API Key
- Secret Key
- Plugin Key

**Facturapi:**
- API URL (e.g., `https://api.facturapi.io`)
- API Key

The UI shows which providers are already configured with a status tag.

## Usage in Code

### Getting the Configured Service

The easiest way to use the branch's invoicing service:

```php
use App\Models\Branch;

$branch = Branch::find(1);

// Get the configured service (based on branch settings)
$invoiceService = $branch->getInvoicingService();

// Create an invoice using the branch's credentials
$result = $invoiceService->createInvoice($invoiceData);
```

### Getting a Specific Provider Service

If you need a specific provider regardless of the branch's default:

```php
// Get Facturacom service with branch credentials
$facturacom = $branch->getFacturacomService();

// Get Facturapi service with branch credentials
$facturapi = $branch->getFacturapiService();
```

### How It Works

1. When you call `$branch->getInvoicingService()`, it checks the branch's `invoicing_provider` setting
2. It then creates the appropriate service instance with the branch's stored credentials
3. If the branch doesn't have custom credentials, it falls back to the global config values

## API Endpoints

The following API endpoints are available for managing branch invoice settings:

### Get Settings
```http
GET /api/branches/{id}/invoice-settings
```

Returns all invoice settings for a branch (credentials are masked).

### Update Settings
```http
PUT /api/branches/{id}/invoice-settings
```

Update invoice settings. Only send fields you want to update.

Example payload:
```json
{
  "invoicing_provider": "facturacom",
  "facturacom_api_url": "https://factura.com/api",
  "facturacom_api_key": "your_api_key",
  "facturacom_secret_key": "your_secret_key",
  "facturacom_plugin_key": "your_plugin_key"
}
```

### Delete Credentials
```http
DELETE /api/branches/{id}/invoice-settings
```

Delete all credentials for a specific provider.

Example payload:
```json
{
  "provider": "facturacom"
}
```

## Settings Storage

All branch settings are stored in the `settings` table with the following structure:

- **settable_type**: `App\Models\Branch`
- **settable_id**: Branch ID
- **key**: Setting name (e.g., `facturacom_api_key`)
- **value**: Encrypted setting value

## Examples

### Example 1: Configure a Branch

```php
$branch = Branch::find(1);

// Set provider
$branch->settings()->set('invoicing_provider', 'facturacom');

// Set Facturacom credentials
$branch->settings()->set('facturacom_api_url', 'https://factura.com/api');
$branch->settings()->set('facturacom_api_key', 'pk_test_...');
$branch->settings()->set('facturacom_secret_key', 'sk_test_...');
$branch->settings()->set('facturacom_plugin_key', 'plugin_...');
```

### Example 2: Create an Invoice

```php
$branch = Branch::find(1);
$service = $branch->getInvoicingService();

$invoiceData = [
    'customer' => [
        'legal_name' => 'Cliente Ejemplo',
        'tax_id' => 'XAXX010101000',
        'email' => 'cliente@example.com',
    ],
    'items' => [
        [
            'description' => 'Producto 1',
            'quantity' => 1,
            'unit_price' => 100.00,
        ],
    ],
];

$result = $service->createInvoice($invoiceData);
```

### Example 3: Switch Provider

```php
$branch = Branch::find(1);

// Switch from Facturacom to Facturapi
$branch->settings()->set('invoicing_provider', 'facturapi');

// Set Facturapi credentials
$branch->settings()->set('facturapi_api_url', 'https://api.facturapi.io');
$branch->settings()->set('facturapi_api_key', 'sk_live_...');

// Now getInvoicingService() will return Facturapi
$service = $branch->getInvoicingService();
```

## Security Notes

1. **Credentials are encrypted** in the database by the laravel-settings package
2. **API responses mask credentials** - they show `••••••••` instead of actual values
3. **Only authenticated users** can access and modify branch settings
4. **Credentials are never logged** in plain text

## Migration from Global Config

If you have existing branches using the global config:

1. They will continue to work without any changes
2. The services automatically fall back to config values if no branch-specific credentials exist
3. Gradually migrate branches by configuring their credentials through the UI
4. Once all branches have their own credentials, you can remove the global config if desired

## Troubleshooting

### Branch doesn't have credentials configured

If a branch doesn't have custom credentials, it will use the values from:
- `config/facturacom.php`
- `config/facturapi.php`

### Credentials not working

1. Verify credentials in the UI show as "Configured"
2. Check that the API URLs are correct
3. Test the credentials directly with the provider's API
4. Check Laravel logs for detailed error messages

### Can't save settings

Make sure:
1. The `settings` table exists (run migrations if needed)
2. The user is authenticated
3. The branch exists and belongs to the user's company
