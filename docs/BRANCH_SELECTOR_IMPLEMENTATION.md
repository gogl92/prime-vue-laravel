# Branch Selector Implementation

## Overview

This document describes the implementation of a global branch selector widget that allows users to dynamically switch between branches. The current branch is stored in the `current_branch_id` column of the `users` table, and branches are filtered by the user's company.

## Features

- **Global Branch Selector**: A dropdown widget displayed in the top navigation bar
- **Company-Based Filtering**: Only shows branches that belong to the user's current company
- **Persistent Selection**: The selected branch is stored in the database and persists across sessions
- **Responsive Design**: Works on both desktop and mobile devices
- **Real-time Updates**: Changes are reflected immediately across the application

## Database Changes

### Migration: Add company_id to branches table

**File**: `database/migrations/2025_11_09_000000_add_company_id_to_branches_table.php`

- Adds a `company_id` foreign key column to the `branches` table
- Establishes a relationship between branches and companies
- Includes cascade delete for data integrity

### Branch Model Updates

**File**: `app/Models/Branch.php`

- Added `company_id` to the `$fillable` array
- Added `company()` relationship method to establish BelongsTo relationship with Company model

## Backend Implementation

This implementation uses **Laravel Orion** for all API interactions, eliminating the need for custom controllers and routes. All operations are handled through the existing Orion resource controllers for `Branch` and `User` models.

## Frontend Implementation

### Composable

**File**: `resources/js/composables/useBranchSelector.ts`

Provides reactive state management and uses **Laravel Orion** for all API interactions:

**State:**
- `branches` - List of available branches
- `currentBranch` - Currently selected branch object
- `currentBranchId` - Currently selected branch ID
- `isLoading` - Loading state indicator
- `error` - Error message state

**Methods:**
- `formatBranchName()` - Formats branch display name with code
- `loadBranches()` - Fetches branches using Orion query builder:
  ```typescript
  const query = Branch.$query()
    .filter('company_id', FilterOperator.Equal, companyId)
    .filter('is_active', FilterOperator.Equal, true)
    .sortBy('name', SortDirection.Asc);
  const response = await query.search();
  ```
- `selectBranch(branchId)` - Updates the current branch using Orion:
  ```typescript
  const user = await User.$query().find(userId);
  await user.$save({ current_branch_id: branchId });
  ```

### Component

**File**: `resources/js/components/BranchSelector.vue`

A PrimeVue Select component that:
- Displays available branches in a dropdown
- Shows the current branch with a building icon
- Handles loading states
- Only displays when branches are available

### Layout Integration

The branch selector has been added to both layout types:

**File**: `resources/js/layouts/app/SidebarLayout.vue`
- Desktop: In the top navigation bar (right side, before user menu)
- Mobile: In the collapsible sidebar (top section)

**File**: `resources/js/layouts/app/HeaderLayout.vue`
- Desktop: In the top menubar (right side, before user menu)
- Mobile: In the drawer menu (top section)

### Translations

**File**: `resources/js/i18n/es.ts`

Added Spanish translations:
- 'Select Branch' → 'Seleccionar Sucursal'
- 'Current Branch' → 'Sucursal Actual'
- 'Failed to load branches' → 'Error al cargar las sucursales'
- 'Failed to update branch' → 'Error al actualizar la sucursal'

## Usage

### Prerequisites

1. Users must have a `current_company_id` set
2. Branches must have a `company_id` assigned
3. Branches must be marked as active (`is_active = true`)

### User Flow

1. User logs into the application
2. The branch selector automatically loads branches for their company
3. User clicks the branch selector dropdown
4. User selects a branch from the list
5. The application updates the user's `current_branch_id` in the database
6. The page reloads to reflect changes across the application

### Data Seeding

To properly use this feature, ensure your seeders or migrations:

1. Assign `company_id` to all existing branches
2. Set `current_company_id` for all users
3. Optionally set a default `current_branch_id` for users

Example seeder snippet:

```php
// In your BranchSeeder or DatabaseSeeder
$company = Company::first();

Branch::create([
    'name' => 'Main Office',
    'code' => 'MAIN',
    'company_id' => $company->id,
    'is_active' => true,
    // ... other fields
]);

// Update users
User::all()->each(function ($user) use ($company) {
    $user->current_company_id = $company->id;
    $user->save();
});
```

## Testing

To test the branch selector:

1. **Run the migration**:
   ```bash
   php artisan migrate
   ```

2. **Seed data**:
   - Ensure branches have `company_id` values
   - Ensure users have `current_company_id` values

3. **Test using Laravel Orion endpoints**:
   ```bash
   # Get branches (using Orion search with filters)
   curl -X POST http://your-app.test/api/branches/search \
     -H "Authorization: Bearer YOUR_TOKEN" \
     -H "Content-Type: application/json" \
     -d '{
       "filters": [
         {"field": "company_id", "operator": "=", "value": 1},
         {"field": "is_active", "operator": "=", "value": true}
       ],
       "sort": [{"field": "name", "direction": "asc"}]
     }'

   # Update current branch (using Orion update)
   curl -X PATCH http://your-app.test/api/users/1 \
     -H "Authorization: Bearer YOUR_TOKEN" \
     -H "Content-Type: application/json" \
     -d '{"current_branch_id": 1}'
   ```

4. **Test the UI**:
   - Log in as a user
   - Check that the branch selector appears in the navigation
   - Select different branches
   - Verify the selection persists after page reload

## Security Considerations

- **Authorization**: Only authenticated users can access branch selection endpoints
- **Company Validation**: Users can only select branches that belong to their company
- **Input Validation**: Branch ID is validated to ensure it exists in the database
- **SQL Injection Prevention**: Uses Eloquent ORM for all database queries

## Future Enhancements

Possible improvements for future iterations:

1. **Branch Permissions**: Restrict which branches a user can access
2. **Branch Context**: Use selected branch to filter data throughout the application
3. **Recent Branches**: Show a list of recently used branches for quick switching
4. **Branch Notifications**: Notify users when switching branches that data may differ
5. **Keyboard Shortcuts**: Add keyboard shortcut to open branch selector
6. **Branch Search**: Add search functionality for users with access to many branches

## Troubleshooting

### Branch selector doesn't appear

- Check that the user has a `current_company_id` set
- Verify that branches exist for the user's company
- Ensure branches are marked as active

### "Branch not found" error

- Verify the branch has a `company_id` that matches the user's `current_company_id`
- Check that the branch exists and is active

### Changes don't persist

- Verify the API endpoint is being called successfully
- Check browser console for errors
- Ensure the user has proper permissions to update their profile

## Files Changed/Created

### Backend
- ✅ Created: `database/migrations/2025_11_09_000000_add_company_id_to_branches_table.php`
- ✅ Modified: `app/Models/Branch.php` - Added company relationship

### Frontend
- ✅ Created: `resources/js/composables/useBranchSelector.ts` - Uses Laravel Orion
- ✅ Created: `resources/js/components/BranchSelector.vue`
- ✅ Modified: `resources/js/models/Branch.ts` - Added company_id field
- ✅ Modified: `resources/js/models/User.ts` - Already had current_branch_id support
- ✅ Modified: `resources/js/types/index.d.ts` - Added current_company_id and current_branch_id to User interface
- ✅ Modified: `resources/js/layouts/app/SidebarLayout.vue`
- ✅ Modified: `resources/js/layouts/app/HeaderLayout.vue`
- ✅ Modified: `resources/js/i18n/es.ts`

### Documentation
- ✅ Created: `docs/BRANCH_SELECTOR_IMPLEMENTATION.md` (this file)

## Architecture Notes

This implementation follows **Laravel Orion best practices**:
- ✅ No custom controllers needed
- ✅ No custom routes needed  
- ✅ Uses Orion's built-in filtering, sorting, and CRUD operations
- ✅ Type-safe TypeScript models that mirror Laravel models
- ✅ Consistent API patterns across the application

