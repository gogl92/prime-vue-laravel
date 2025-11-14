# Invoice Export Feature

This document explains the invoice export functionality using maatwebsite/excel.

## Overview

The invoice export feature allows users to export invoice data to Excel (.xlsx) format with support for filtering, formatting, and styling.

## Features

- ✅ **Excel Export**: Export invoices to .xlsx format
- ✅ **Filter Support**: Export respects current filters (search, city, country)
- ✅ **Professional Formatting**: Auto-sized columns, frozen header row, styled headers
- ✅ **Comprehensive Data**: Includes client info, issuer info, amounts, status, and more
- ✅ **Authenticated**: Requires authentication to export

## Architecture

### Base Classes

**ExportableModel Interface** (`app/Contracts/ExportableModel.php`)
- Defines the contract for exportable models
- Methods: `collection()`, `headings()`, `columnFormats()`

**BaseReportExport** (`app/Exports/BaseReportExport.php`)
- Abstract base class for all report exports
- Provides common styling and formatting
- Implements header styling (blue background, white text, bold)
- Auto-filters and freezes first row
- Helper method `getSafeValue()` for safe array access

### Invoice Export

**InvoiceExport** (`app/Exports/InvoiceExport.php`)
- Extends BaseReportExport
- Exports invoice data with client and issuer information
- Supports custom query filtering via `setQuery()` method
- Formats numbers, dates, and text appropriately

**InvoiceExportController** (`app/Http/Controllers/Api/InvoiceExportController.php`)
- Handles export requests
- Applies filters from request parameters
- Returns downloadable Excel file

## Usage

### From the UI

1. Navigate to the Invoices page (`/invoices`)
2. Apply any desired filters (search, city, country)
3. Click **"Export to Excel"** button
4. File will be downloaded as `invoices_YYYY-MM-DD.xlsx`

### Exported Columns

The export includes the following columns:

| Column | Description | Format |
|--------|-------------|--------|
| Folio | Invoice folio/series | Text |
| Date | Invoice date | Date (YYYY-MM-DD) |
| Client Name | Client's legal name | Text |
| Client RFC | Client's tax ID | Text |
| Client Email | Client's email address | Text |
| Issuer Name | Issuer's legal name | Text |
| Issuer RFC | Issuer's tax ID | Text |
| Subtotal | Invoice subtotal | Number (comma separated) |
| Taxes | Invoice taxes | Number (comma separated) |
| Total | Invoice total | Number (comma separated) |
| Currency | Invoice currency | Text |
| Payment Method | Payment method code | Text |
| Payment Form | Payment form code | Text |
| Status | Invoice status | Text |
| CFDI Use | CFDI use code | Text |
| Has XML | Whether XML file exists | Text (Yes/No) |
| Has PDF | Whether PDF file exists | Text (Yes/No) |
| UUID | Fiscal folio UUID | Text |
| Created At | Creation timestamp | DateTime |

### API Endpoint

```http
GET /api/invoices/export
Authorization: Bearer {token}
```

**Query Parameters:**
- `search` - Full-text search term
- `filters[client.city]` - Filter by client city
- `filters[client.country]` - Filter by client country
- `date_from` - Filter invoices from this date
- `date_to` - Filter invoices up to this date
- `status` - Filter by invoice status

**Example:**
```
GET /api/invoices/export?search=ABC123&filters[status]=completed
```

## Programmatic Usage

### Creating an Export

```php
use App\Exports\InvoiceExport;
use Maatwebsite\Excel\Facades\Excel;

// Basic export (all invoices)
return Excel::download(new InvoiceExport(), 'invoices.xlsx');

// With custom query
$export = new InvoiceExport();
$export->setQuery(
    Invoice::query()
        ->where('status', 'completed')
        ->whereBetween('date', ['2024-01-01', '2024-12-31'])
);

return Excel::download($export, 'completed_invoices_2024.xlsx');
```

### Creating Custom Exports

To create a new export class:

```php
use App\Exports\BaseReportExport;
use PhpOffice\PhpSpreadsheet\Style\NumberFormat;
use Illuminate\Support\Collection;

class MyCustomExport extends BaseReportExport
{
    public function collection(): Collection
    {
        return MyModel::all()->map(function ($item) {
            return [
                'column1' => $item->field1,
                'column2' => $item->field2,
            ];
        });
    }

    public function headings(): array
    {
        return ['Column 1', 'Column 2'];
    }

    public function columnFormats(): array
    {
        return [
            'A' => NumberFormat::FORMAT_TEXT,
            'B' => NumberFormat::FORMAT_NUMBER,
        ];
    }
}
```

## Styling Configuration

Header styles are configured in `config/excel.php`:

```php
'exports' => [
    'styles' => [
        'header' => [
            'font' => [
                'bold' => true,
                'size' => 12,
            ],
            'color' => [
                'rgb' => 'FFFFFF', // White text
            ],
            'fill' => [
                'fillType' => \PhpOffice\PhpSpreadsheet\Style\Fill::FILL_SOLID,
                'startColor' => [
                    'rgb' => '4472C4', // Blue background
                ],
                'endColor' => [
                    'rgb' => '4472C4',
                ],
            ],
        ],
    ],
],
```

## Excel Features

Exported files include:

1. **Auto Filter**: First row has auto-filter enabled for easy filtering in Excel
2. **Frozen Header**: Header row is frozen so it stays visible when scrolling
3. **Auto-sized Columns**: Columns automatically resize to fit content
4. **Styled Headers**: Blue background, white bold text
5. **Number Formatting**: Proper formatting for currencies, dates, and numbers

## Common Column Formats

```php
use PhpOffice\PhpSpreadsheet\Style\NumberFormat;

// Common formats
NumberFormat::FORMAT_TEXT                      // Text
NumberFormat::FORMAT_NUMBER                    // Number
NumberFormat::FORMAT_NUMBER_COMMA_SEPARATED1   // 1,234.56
NumberFormat::FORMAT_CURRENCY_USD              // $1,234.56
NumberFormat::FORMAT_DATE_YYYYMMDD2            // 2024-01-15
NumberFormat::FORMAT_DATE_DATETIME             // 2024-01-15 14:30:00
NumberFormat::FORMAT_PERCENTAGE                // 50%
```

## Troubleshooting

### Export button not working
- Check browser console for errors
- Verify auth token is present in localStorage
- Check API route is accessible

### Empty export file
- Verify query is returning data
- Check filters are not too restrictive
- Review `collection()` method in export class

### Formatting issues
- Check column format mappings in `columnFormats()`
- Verify data types in `collection()` method
- Review `config/excel.php` for style settings

### Memory issues with large exports
- Use chunking in the `collection()` method
- Adjust `chunk_size` in `config/excel.php`
- Consider implementing queue-based exports for very large datasets

## Future Enhancements

Potential improvements:

- Add date range pickers for export filters
- Email exports instead of direct download
- Schedule automated exports
- Support for CSV format
- Add charts and pivot tables
- Export invoices with line items on separate sheets
