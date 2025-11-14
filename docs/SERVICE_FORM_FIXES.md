# Service Form Fixes - November 14, 2025

## Issues Fixed

### 1. Database Error - Missing Blameable Columns
**Error**: `SQLSTATE[42S22]: Column not found: 1054 Unknown column 'updated_by' in 'field list'`

**Root Cause**: The `services` table was missing the blameable columns (`created_by`, `updated_by`, `deleted_by`) that are required by the `BlameableTrait` used in the Service model.

**Solution**: Created and ran migration `2025_11_14_042704_add_blameable_columns_to_services_table.php` to add:
- `created_by` (integer, nullable)
- `updated_by` (integer, nullable)  
- `deleted_by` (integer, nullable)

### 2. Price Input Not Working
**Issue**: Unable to set or edit the price value in the service form.

**Root Cause**: PrimeVue's InputNumber component in `currency` mode with initial value of `0` was not handling input changes properly.

**Solutions Applied**:
1. Changed price initial value from `0` to `null` in formData
2. Switched InputNumber from `mode="currency"` to `mode="decimal"` with `prefix="$"`
3. Added `fluid` prop for better responsive behavior
4. Added explicit validation for price before saving
5. Kept decimal precision settings (`min-fraction-digits="2"`, `max-fraction-digits="2"`)

### 3. TypeScript Errors
Fixed type safety issues:
- Line 56: Added optional chaining for `branches.value[0]?.$attributes?.id`
- Line 84: Added nullish coalescing for `service.$attributes.duration ?? null`

## Files Modified

1. **database/migrations/2025_11_14_042704_add_blameable_columns_to_services_table.php** (new)
   - Adds blameable columns to services table

2. **resources/js/pages/settings/ServiceForm.vue**
   - Changed price initial value from `0` to `null`
   - Updated InputNumber component configuration
   - Added price validation
   - Fixed TypeScript type errors

## Testing Checklist

- [x] Migration runs successfully
- [ ] Can create a new service with price
- [ ] Can edit an existing service and change price
- [ ] Price displays correctly in decimal format with $ prefix
- [ ] Form validation prevents saving without price
- [ ] No TypeScript or linter errors

## Technical Details

### InputNumber Component Changes
```vue
<!-- Before -->
<InputNumber
  v-model="formData.price"
  mode="currency"
  currency="USD"
  locale="en-US"
  :min="0"
  :min-fraction-digits="2"
  :max-fraction-digits="2"
/>

<!-- After -->
<InputNumber
  v-model="formData.price"
  mode="decimal"
  prefix="$"
  :min="0"
  :min-fraction-digits="2"
  :max-fraction-digits="2"
  fluid
/>
```

### Migration SQL
```sql
ALTER TABLE services 
  ADD COLUMN created_by INT NULL AFTER is_active,
  ADD COLUMN updated_by INT NULL AFTER created_by,
  ADD COLUMN deleted_by INT NULL AFTER updated_by;
```

## Notes

- The `mode="decimal"` with `prefix="$"` is more reliable than `mode="currency"` for PrimeVue InputNumber
- The `fluid` prop ensures the input takes full width responsively
- Blameable columns are consistently defined as `integer` (not `foreignId`) across the codebase

