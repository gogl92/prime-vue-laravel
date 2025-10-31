# Invoice Schema Refactoring Summary

## Overview
This document summarizes the major refactoring done to align the CreateInvoice.vue frontend form with the backend database schema. The main change was moving client/customer information to a dedicated `clients` table and adding all missing CFDI-related fields to the invoices table.

## Database Changes

### 1. New Clients Table
**Migration:** `2025_10_17_151236_create_clients_table.php`

Created a new `clients` table with the following fields:
- `id` (primary key)
- `name` (string)
- `email` (string, unique)
- `phone` (string)
- `address` (string, nullable)
- `city` (string, nullable)
- `state` (string, nullable)
- `zip` (string, nullable)
- `country` (string, nullable)
- `is_supplier` (boolean, default: false)
- `is_issuer` (boolean, default: false)
- `timestamps`

**Purpose:** Store client/customer information separately, allowing clients to be reused across multiple invoices. Clients can be marked as suppliers or issuers (invoice-generating companies).

### 2. Updated Invoices Table
**Migration:** `2025_10_17_151248_update_invoices_table_add_missing_fields.php`

**Removed Fields:**
- `name`, `email`, `phone`, `address`, `city`, `state`, `zip`, `country`

**Added Fields:**
- `client_id` (foreign key to clients table)
- `issuer_id` (foreign key to clients table, nullable)
- `cfdi_type` (string, default: 'Factura')
- `order_number` (string, nullable)
- `invoice_date` (date, nullable)
- `payment_form` (string, nullable)
- `send_email` (boolean, default: true)
- `payment_method` (string, nullable)
- `cfdi_use` (string, nullable)
- `series` (string, nullable)
- `exchange_rate` (decimal(10,4), default: 1.0000)
- `currency` (string, default: 'MXN')
- `comments` (text, nullable)

## Model Changes

### 1. New Client Model
**File:** `app/Models/Client.php`

- Added all fillable fields matching the migration
- Implemented `SafePhoneNumberCast` for phone numbers
- Added boolean casts for `is_supplier` and `is_issuer`
- Relationships:
  - `invoices()` - HasMany relationship for invoices where this client is the customer
  - `issuedInvoices()` - HasMany relationship for invoices where this client is the issuer

### 2. Updated Invoice Model
**File:** `app/Models/Invoice.php`

- Removed old address-related fields
- Added new CFDI-related fields to fillable array
- Added proper casts:
  - `invoice_date` → 'date'
  - `send_email` → 'boolean'
  - `exchange_rate` → 'decimal:4'
- Relationships:
  - `client()` - BelongsTo relationship to Client (customer)
  - `issuer()` - BelongsTo relationship to Client (issuer)
  - Kept existing `products()` and `payments()` relationships

## Factory Changes

### 1. New ClientFactory
**File:** `database/factories/ClientFactory.php`

- Generates realistic company data
- Helper methods:
  - `supplier()` - Creates a client marked as supplier
  - `issuer()` - Creates a client marked as issuer

### 2. Updated InvoiceFactory
**File:** `database/factories/InvoiceFactory.php`

- Updated to use `client_id` and `issuer_id` foreign keys
- Added realistic CFDI-related data generation
- Uses Client factories for relationships

## Seeder Changes

### 1. New ClientSeeder
**File:** `database/seeders/ClientSeeder.php`

Creates:
- 3 issuer companies
- 10 suppliers
- 20 regular clients

### 2. Updated DatabaseSeeder
**File:** `database/seeders/DatabaseSeeder.php`

- Added `ClientSeeder::class` before `InvoiceSeeder::class` (to ensure clients exist before invoices)

## API Changes

### 1. New ClientController
**File:** `app/Http/Controllers/ClientController.php`

- Standard Orion REST controller for Client resource

### 2. Updated API Routes
**File:** `routes/api.php`

- Added: `Orion::resource('clients', ClientController::class);`

## Frontend Changes

### 1. New Client TypeScript Model
**File:** `resources/js/models/Client.ts`

- Matches the backend Client model structure
- Includes all fields and proper typing

### 2. Updated Invoice TypeScript Model
**File:** `resources/js/models/Invoice.ts`

- Updated to match new backend schema
- Added `client_id` and `issuer_id` foreign keys
- Added all CFDI-related fields
- Added nested `client` and `issuer` relationship types

### 3. Updated CreateInvoice.vue Component
**File:** `resources/js/pages/CreateInvoice.vue`

**Major Changes:**
- Removed manual customer information input fields
- Added client loading functionality with `loadClients()` method
- Added computed property `issuerClients` to filter clients marked as issuers
- Updated form structure:
  - Replaced customer fields with `client_id` dropdown (filterable)
  - Added `issuer_id` dropdown (filtered to show only issuers)
  - Added display-only section showing selected client's information
- Updated `saveInvoice()` method:
  - Converts Date objects to ISO string format
  - Uses proper Orion API for attaching products
  - Handles null/undefined values properly
- Fixed all TypeScript linter errors
- Updated field names to match English standards (e.g., `date` → `invoice_date`)

## Field Naming Conventions

All field names now follow English naming standards:
- ✅ `cfdi_use` (not "uso de cfdi")
- ✅ `cfdi_type` (not "tipo de CFDI")
- ✅ `payment_form` (not "forma pago")
- ✅ `payment_method` (not "método de pago")
- ✅ `invoice_date` (not "fecha")
- ✅ `order_number` (not "número de órden")

## Migration Instructions

To apply these changes to an existing database:

1. **Backup your database first!**

2. Run the migrations:
   ```bash
   php artisan migrate
   ```

3. Seed the new data:
   ```bash
   php artisan db:seed --class=ClientSeeder
   ```

4. If you have existing invoices, you'll need to manually migrate the data:
   - Create Client records from existing invoice data
   - Update invoices to reference the new client IDs
   - This was not automated as it requires business logic decisions

## Testing Considerations

After applying these changes, make sure to:
1. Update any existing tests that reference invoice fields
2. Create tests for the new Client model and relationships
3. Test the CreateInvoice.vue form thoroughly
4. Verify API endpoints work correctly with authentication
5. Test the product attachment functionality

## Benefits of This Refactoring

1. **Data Normalization**: Client information is stored once and reused
2. **Flexibility**: Clients can be both customers and suppliers/issuers
3. **Completeness**: All CFDI fields are now in the database
4. **Type Safety**: Frontend has proper TypeScript types
5. **Maintainability**: Clear separation between clients and invoices
6. **Standards Compliance**: Follows English naming conventions consistently

