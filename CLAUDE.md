# CLAUDE.md

This file provides guidance to Claude Code (claude.ai/code) when working with code in this repository.

## Project Overview

This is a Laravel 12 + Vue 3 + PrimeVue 4 starter kit built with Inertia.js for server-rendered Vue components and Laravel Orion for automatic REST API generation. It uses TypeScript, Tailwind CSS 4, and Sanctum for authentication.

## Development Commands

### Starting the Development Environment

```bash
sail composer dev
```
This runs concurrently: Laravel dev server (port 8000), queue listener, Pail logs, and Vite dev server (port 5173).

Individual commands if needed:
```bash
sail artisan serve             # Laravel server on :8000
sail artisan queue:listen      # Queue worker
sail artisan pail             # Real-time logs
npm run dev                   # Vite dev server with HMR
```

**Note**: Always use `sail` or the `sail` alias for PHP, Artisan, and Composer commands to ensure they run inside the Docker container.

### Building for Production

```bash
npm run build                 # Client-side build
npm run build:ssr            # Build with SSR support
```

### Testing & Quality

```bash
sail composer test           # Run PHPUnit tests (clears config first)
sail composer analyse        # Run PHPstan static analysis (2GB memory)
npm run lint                 # ESLint with auto-fix
npm run format               # Format code with Prettier
npm run format:check         # Check code formatting without making changes
```

### Database

```bash
sail artisan migrate         # Run migrations
sail artisan migrate:fresh   # Fresh migration (drops all tables)
sail artisan db:seed         # Run seeders
```

## Architecture Overview

### Full-Stack Integration Pattern

This application uses **two parallel routing systems**:

1. **Inertia.js Routes** (`routes/web.php`) - Server-rendered Vue pages with session auth
   - Pages in `resources/js/pages/`
   - Props passed from Laravel controllers via `Inertia::render()`
   - Session-based authentication

2. **Orion API Routes** (`routes/api.php`) - REST API with token auth
   - Controllers extend `BaseOrionController` in `app/Http/Controllers/`
   - Auto-generated RESTful endpoints with search, filter, sort, pagination
   - Sanctum token authentication
   - Frontend consumes via Orion model classes in `resources/js/models/`

### Backend Architecture (Laravel)

#### Laravel Orion Controller Pattern

All API controllers extend `app/Http/Controllers/BaseOrionController.php` and define:

```php
protected $model = ModelClass::class;
protected $storeRequest = StoreModelRequest::class;
protected $updateRequest = UpdateModelRequest::class;

public function searchableBy(): array        // Fields for full-text search
public function filterableBy(): array        // Fields for filtering (?filters[field]=value)
public function sortableBy(): array          // Fields for sorting (?sort=field)
public function includes(): array            // Eager-loaded relationships
public function limit(): int                 // Pagination limit (default 10)
```

#### Orion Routes Configuration

Routes are registered in `routes/api.php`:

```php
Orion::resource('invoices', InvoiceController::class);
Orion::belongsToManyResource('invoices', 'products', InvoiceProductsController::class);
Orion::hasManyResource('invoices', 'payments', InvoicePaymentsController::class);
```

This auto-generates:
- `GET /api/invoices` - Index with pagination, search, filter, sort
- `POST /api/invoices` - Create
- `GET /api/invoices/{id}` - Show
- `PUT/PATCH /api/invoices/{id}` - Update
- `DELETE /api/invoices/{id}` - Delete
- `GET /api/invoices/{id}/products` - Relationship endpoints
- `GET /api/invoices/{id}/payments` - Relationship endpoints

**Important**: All Orion controllers require `auth()->check()` - all API endpoints are protected and require authentication.

#### Model Relationships

Current schema has three main entities with relationships:

```
Invoice
  ├─ hasMany(Payment)           # Invoice payments
  └─ belongsToMany(Product)     # Products on invoice (pivot: quantity, price)

Product
  └─ belongsToMany(Invoice)     # Invoices containing product

Payment
  └─ belongsTo(Invoice)         # Parent invoice
```

Relationship pivots and foreign keys use cascade deletes where appropriate.

### Frontend Architecture (Vue 3 + TypeScript)

#### Directory Structure

```
resources/js/
├── app.ts                      # Client entry: PrimeVue, Inertia, Ziggy setup
├── ssr.ts                      # SSR entry point
├── pages/                      # Inertia page components (auto-discovered)
│   ├── InvoicesExample.vue    # Full CRUD example with Orion API
│   └── auth/, settings/        # Feature-based organization
├── layouts/                    # Shared layouts
│   ├── AppLayout.vue          # Main authenticated layout
│   └── app/HeaderLayout.vue, SidebarLayout.vue
├── components/                 # Reusable components
│   └── primevue/              # PrimeVue component wrappers
├── composables/               # Vue 3 composition API
│   ├── usePaginatedData.ts   # State for filters, sorting, pagination
│   ├── useAuthToken.ts       # Sanctum token management
│   └── useAppLayout.ts       # Navigation state
├── models/                    # Orion model classes (TypeScript)
│   ├── Invoice.ts            # Typed API client for invoices
│   ├── Product.ts
│   └── Payment.ts
├── services/
│   └── orion.ts              # Orion service initialization
├── i18n/                     # Internationalization
│   ├── index.ts              # i18n configuration
│   ├── en.ts                 # English (empty - base language)
│   ├── es.ts                 # Spanish translations
│   └── primevue/             # PrimeVue component locales
│       ├── en.ts             # PrimeVue English locale
│       └── es.ts             # PrimeVue Spanish locale
├── types/                    # TypeScript definitions
└── theme/                    # PrimeVue theme presets
```

#### Orion Model Classes

Frontend models in `resources/js/models/` extend `@tailflow/laravel-orion` Model class:

```typescript
import { Model } from '@tailflow/laravel-orion/lib/model'

export class Invoice extends Model<InvoiceAttributes, InvoiceRelations> {
  public $resource(): string {
    return 'invoices'  // Maps to /api/invoices
  }
}
```

Usage pattern:
```typescript
// Query builder
const invoices = await Invoice.$query()
  .with(['products', 'payments'])
  .search('search term')
  .sort('created_at')
  .limit(15)
  .get()

// CRUD operations
const invoice = new Invoice({ name: 'Example' })
await invoice.$save()                  // POST /api/invoices
await invoice.$refresh()               // GET /api/invoices/{id}
await invoice.$destroy()               // DELETE /api/invoices/{id}
```

#### Composables Pattern

**`usePaginatedData()`** - Manages table state with Inertia:
```typescript
const { data, loading, filters, sort, page, limit, totalRecords, loadData } = usePaginatedData()
```
- Syncs state with URL query params (filter persistence)
- Debounces filter input (300ms)
- Uses `router.visit()` for server navigation
- Handles both Inertia and Orion responses

**`useAuthToken()`** - Manages Sanctum tokens:
```typescript
const { setToken, removeToken, getToken } = useAuthToken()
```
- Stores token in localStorage as `auth_token`
- Syncs with Orion service on updates
- Auto-loads from Inertia flash on page load

#### Inertia Shared Data

`app/Http/Middleware/HandleInertiaRequests.php` shares to all pages:
- `auth` - Current user + flash auth token
- `flash` - Toast messages (success, error, warn, info, message)
- `colorScheme` - Dark/light mode from cookie
- `ziggy` - Laravel routes helper
- `queryParams` - URL query params for filter persistence

Access in components via `$page.props`:
```vue
<script setup lang="ts">
import { usePage } from '@inertiajs/vue3'
const page = usePage<AppPageProps>()
const user = page.props.auth.user
</script>
```

### Authentication System

**Dual authentication model**:

1. **Session Auth** (Inertia pages) - `routes/auth.php` + `routes/web.php`
   - Middleware: `auth`, `verified`
   - Login/register/password reset/email verification
   - Controllers: `app/Http/Controllers/Auth/`

2. **Token Auth** (API) - `routes/api.php`
   - Middleware: `auth:sanctum`
   - Endpoints: `POST /api/auth/login`, `POST /api/auth` (register), `GET /api/auth`, `DELETE /api/auth`
   - Controller: `app/Http/Controllers/Api/AuthController.php`
   - Tokens stored in `personal_access_tokens` table

**Authorization**: Currently all authenticated users have same permissions. To add granular permissions, implement Laravel policies and update `BaseOrionController` methods like `authorizeIndex()`, `authorizeStore()`, etc.

### PrimeVue Integration

**Theme System**:
- Multiple presets available in `resources/js/theme/`
- Default: `noir-preset.ts` (CSS variables-based)
- Dark mode: `.dark` CSS class on `<html>`
- Global pass-through styling: `theme/global-pt.ts`
- Switch themes via `useThemePreset()` composable

**Auto-import**: PrimeVue components auto-imported via `unplugin-vue-components` with `@primevue/auto-import-resolver`. No manual imports needed.

**Toast Notifications**:
- Use PrimeVue Toast component in `AppLayout.vue`
- Triggered by Inertia flash messages
- Backend: throw `new ErrorToastException('message', 'error')` for user feedback

### Internationalization (i18n)

**Vue I18n** is configured for multi-language support:
- Configuration: `resources/js/i18n/index.ts`
- Language files: `resources/js/i18n/en.ts` (base/fallback) and `resources/js/i18n/es.ts`
- PrimeVue locales: `resources/js/i18n/primevue/en.ts` and `resources/js/i18n/primevue/es.ts`
- Default locale: `en` (English)
- Composition API mode enabled (`legacy: false`)
- PrimeVue locale automatically switches with i18n locale

**Translation Pattern** - Use English text as keys:
- **DO NOT** create special keys like `auth.login` or `common.submit`
- **DO** use the English text directly as the translation key
- The `en.ts` file remains **empty** (English is the fallback)
- Only add translations to other language files

**Example**:
```vue
<script setup lang="ts">
import { useI18n } from 'vue-i18n'
const { t, locale } = useI18n()
</script>

<template>
  <h1>{{ t('Welcome to the Dashboard') }}</h1>
  <Button @click="locale = 'es'">{{ t('Switch Language') }}</Button>
</template>
```

**Spanish translations** (`resources/js/i18n/es.ts`):
```typescript
export default {
  'Welcome to the Dashboard': 'Bienvenido al Panel',
  'Switch Language': 'Cambiar idioma',
};
```

**English translations** (`resources/js/i18n/en.ts`):
```typescript
export default {}; // Empty - English text is used as-is
```

**PrimeVue Component Translations**:
- PrimeVue components (Calendar, DataTable, FileUpload, etc.) use separate locale files
- Located in `resources/js/i18n/primevue/`
- Include component-specific strings (date names, buttons, aria labels)
- Automatically applied when i18n locale changes

**Adding new translations**:
1. Write English text directly in `t()` function: `{{ t('Hello World') }}`
2. Add translation to `resources/js/i18n/es.ts`: `'Hello World': 'Hola Mundo'`
3. Do NOT add anything to `en.ts` - keep it empty

## Key Configuration Files

- `config/orion.php` - Orion API configuration (namespaces, auth guard, search settings)
- `config/sanctum.php` - Sanctum token config (stateful domains, guards, expiration)
- `config/inertia.php` - Inertia SSR settings
- `vite.config.ts` - Vite build config with Laravel plugin, Vue plugin, Tailwind
- `tsconfig.json` - TypeScript configuration with strict mode

## Important Conventions

### **CRITICAL: New Development Standard**

**ALL new TypeScript services and Vue components MUST use Laravel Orion with Sanctum authentication. DO NOT use Inertia.js for new features.**

- New pages should be standalone Vue components (not Inertia pages)
- All data fetching must go through Orion model classes
- Authentication must use Sanctum tokens via `useAuthToken()` composable
- Use direct API communication instead of Inertia's server-side rendering
- Inertia pages exist only for legacy/authentication flows - do not extend this pattern

### API Development Workflow

1. **Create Model & Migration**
   ```bash
   php artisan make:model ModelName -m
   ```

2. **Create Orion Controller**
   ```bash
   php artisan make:controller ModelNameController
   ```
   Extend `BaseOrionController`, define `$model`, requests, and methods

3. **Create Form Requests**
   ```bash
   php artisan make:request StoreModelNameRequest
   php artisan make:request UpdateModelNameRequest
   ```

4. **Register Route** in `routes/api.php`
   ```php
   Orion::resource('model-names', ModelNameController::class);
   ```

5. **Create Frontend Model** in `resources/js/models/ModelName.ts`
   ```typescript
   export class ModelName extends Model<Attributes, Relations> {
     public $resource(): string {
       return 'model-names'
     }
   }
   ```

### Frontend Development (Orion-First Approach)

**For new features, use Orion models directly - NOT Inertia pages:**

1. **Create Vue Component** in `resources/js/components/` or `resources/js/pages/` (as standalone component)
2. **Use Orion Models** for all data operations:
   ```typescript
   // In component setup
   import { Invoice } from '@/models/Invoice'

   const loadData = async () => {
     const invoices = await Invoice.$query()
       .with(['payments', 'products'])
       .limit(20)
       .get()
   }
   ```
3. **Authentication**: Ensure Sanctum token is set via `useAuthToken()`
4. **Use AppLayout** for consistent navigation (if needed)
5. **Use Composables** for state management (`usePaginatedData`, etc.)
6. **Route via Vue Router** or direct component mounting (NOT Inertia routing)

**Legacy Inertia Pages**: Only maintain existing Inertia pages for authentication flows. Do not create new Inertia pages.

### Shared State Management

- No Vuex/Pinia - use reactive composables for state
- For Orion-based components: Use Vue reactivity + composables (no Inertia props)
- For legacy Inertia pages: Use Inertia props + `router.visit()`
- Persist filter/sort state in URL query params (if using Inertia) or component state
- LocalStorage for client-only state (theme, auth token)

## Database Conventions

- SQLite for development (`database/database.sqlite`)
- Migrations use standard Laravel conventions
- Pivot tables: singular model names in alphabetical order (`invoice_product`)
- Foreign keys: `{model}_id` with `constrained()->cascadeOnDelete()` where appropriate
- Enum types for status fields (e.g., `payment_method`, `status` in payments)

## Phone Number Validation

**Laravel-Phone** package is installed for international phone number validation and formatting:
- Package: `propaganistas/laravel-phone`
- Default countries: US and Mexico (`US`, `MX`)

**Usage in Form Requests**:
```php
use Propaganistas\LaravelPhone\Rules\Phone;

public function rules(): array
{
    return [
        'phone' => ['required', 'string', (new Phone())->country(['US', 'MX'])],
    ];
}
```

**Usage in Models** (automatic formatting to E.164):
```php
use Propaganistas\LaravelPhone\Casts\E164PhoneNumberCast;

protected function casts(): array
{
    return [
        'phone' => E164PhoneNumberCast::class . ':US,MX',
    ];
}
```

**Benefits**:
- Validates phone numbers against specific countries
- Automatically formats to E.164 standard (e.g., `+15551234567`)
- Handles various input formats (with/without country code, spaces, dashes)
- Returns PhoneNumber object with formatting methods

**Example**: Invoice model uses phone validation and E.164 casting for US and Mexico numbers

## Styling Approach

- **Tailwind CSS 4** for utility-first styling
- **PrimeVue components** for UI primitives
- **CSS variables** for theming (defined in presets)
- **Dark mode** via `.dark` class selector
- **Pass-through props** for component customization
- Avoid writing custom CSS - prefer Tailwind utilities + PrimeVue theming

## Code Formatting

- **Prettier** is configured for automatic code formatting
- Configuration in `.prettierrc` with sensible defaults for Vue/TypeScript
- Integrated with ESLint via `eslint-config-prettier` to avoid conflicts
- Run `npm run format` before committing code
- Settings: single quotes, semicolons enabled, 2-space indentation, 100 char print width

## Error Handling

- Orion API errors caught in `app.ts` via `router.on('invalid')`
- Backend validation errors returned as Orion error responses
- `ErrorToastException` for user-facing error messages
- Toast notifications for success/error feedback
- Console logging for debugging in development

## Testing

- PHPUnit for backend tests (`tests/` directory)
- Run with `composer test`
- Frontend: No test framework configured yet
- Add Vitest + Vue Test Utils for component testing if needed

## Additional Notes

- **Ziggy**: Laravel routes available in Vue via `route()` helper
- **SSR**: Configured but optional - use `npm run build:ssr` for SSR builds
- **Queue**: Queue listener runs with `composer dev` for background jobs
- **Logs**: Use Pail (`php artisan pail`) for real-time log viewing
- **Helpers**: Global PHP helpers in `app/helpers.php` (autoloaded)
