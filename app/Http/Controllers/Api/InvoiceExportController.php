<?php

declare(strict_types=1);

namespace App\Http\Controllers\Api;

use App\Exports\InvoiceExport;
use App\Http\Controllers\Controller;
use App\Models\Invoice;
use Illuminate\Http\Request;
use Maatwebsite\Excel\Facades\Excel;
use Symfony\Component\HttpFoundation\BinaryFileResponse;

class InvoiceExportController extends Controller
{
    /**
     * Export invoices to Excel
     *
     * @param Request $request
     * @return BinaryFileResponse
     */
    public function export(Request $request): BinaryFileResponse
    {
        // Check authorization
        if (!auth()->check()) {
            abort(401, 'Unauthorized');
        }

        // Build query with filters
        $query = Invoice::query()->with(['client', 'issuer']);

        // Apply filters if provided (similar to Orion's filtering)
        if ($request->has('filters')) {
            $filters = $request->input('filters');

            foreach ($filters as $field => $value) {
                if ($value !== null && $value !== '') {
                    // Handle relation filters (e.g., client.name)
                    if (str_contains($field, '.')) {
                        [$relation, $column] = explode('.', $field, 2);
                        $query->whereHas($relation, function ($q) use ($column, $value) {
                            $q->where($column, 'LIKE', "%{$value}%");
                        });
                    } else {
                        $query->where($field, 'LIKE', "%{$value}%");
                    }
                }
            }
        }

        // Apply search if provided
        if ($request->has('search') && $request->input('search')) {
            $search = $request->input('search');
            $query->where(function ($q) use ($search) {
                $q->where('series', 'LIKE', "%{$search}%")
                    ->orWhere('uuid', 'LIKE', "%{$search}%")
                    ->orWhere('status', 'LIKE', "%{$search}%")
                    ->orWhereHas('client', function ($clientQuery) use ($search) {
                        $clientQuery->where('name', 'LIKE', "%{$search}%")
                            ->orWhere('rfc', 'LIKE', "%{$search}%");
                    });
            });
        }

        // Apply date range filter if provided
        if ($request->has('date_from')) {
            $query->whereDate('date', '>=', $request->input('date_from'));
        }

        if ($request->has('date_to')) {
            $query->whereDate('date', '<=', $request->input('date_to'));
        }

        // Apply status filter
        if ($request->has('status') && $request->input('status')) {
            $query->where('status', $request->input('status'));
        }

        // Create export instance
        $export = new InvoiceExport();
        $export->setQuery($query);

        // Generate filename with timestamp
        $filename = 'invoices_' . now()->format('Y-m-d_His') . '.xlsx';

        // Return download
        return Excel::download($export, $filename);
    }
}
