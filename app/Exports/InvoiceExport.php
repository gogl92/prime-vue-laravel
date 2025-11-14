<?php

declare(strict_types=1);

namespace App\Exports;

use PhpOffice\PhpSpreadsheet\Style\NumberFormat;
use Illuminate\Support\Collection;
use App\Models\Invoice;
use Illuminate\Database\Eloquent\Builder;

class InvoiceExport extends BaseReportExport
{
    protected ?Builder $query = null;

    /**
     * Set a custom query for filtering invoices
     *
     * @param Builder $query
     * @return self
     */
    public function setQuery(Builder $query): self
    {
        $this->query = $query;
        return $this;
    }

    /**
     * Get the collection of invoices to export
     *
     * @return Collection
     */
    public function collection(): Collection
    {
        $query = $this->query ?? Invoice::query();

        return $query
            ->with(['client', 'issuer'])
            ->get()
            ->map(function ($invoice) {
                return [
                    'folio' => $invoice->series ?? '-',
                    'date' => $invoice->date ? \Carbon\Carbon::parse($invoice->date)->format('Y-m-d') : '-',
                    'client_name' => $invoice->client?->name ?? '-',
                    'client_rfc' => $invoice->client?->rfc ?? '-',
                    'client_email' => $invoice->client?->email ?? '-',
                    'issuer_name' => $invoice->issuer?->legal_name ?? '-',
                    'issuer_rfc' => $invoice->issuer?->rfc ?? '-',
                    'sub_total' => $invoice->sub_total ?? 0,
                    'taxes' => $invoice->taxes ?? 0,
                    'total' => $invoice->import ?? 0,
                    'currency' => $invoice->currency ?? 'MXN',
                    'payment_method' => $invoice->payment_method ?? '-',
                    'payment_form' => $invoice->payment_form ?? '-',
                    'status' => $invoice->status ?? 'pending',
                    'cfdi_use' => $invoice->cfdi_use ?? '-',
                    'xml_path' => $invoice->xml_path ? 'Yes' : 'No',
                    'pdf_path' => $invoice->pdf_path ? 'Yes' : 'No',
                    'uuid' => $invoice->uuid ?? '-',
                    'created_at' => $invoice->created_at ? $invoice->created_at->format('Y-m-d H:i:s') : '-',
                ];
            });
    }

    /**
     * Get the headings for the export
     *
     * @return array<int, string>
     */
    public function headings(): array
    {
        return [
            'Folio',
            'Date',
            'Client Name',
            'Client RFC',
            'Client Email',
            'Issuer Name',
            'Issuer RFC',
            'Subtotal',
            'Taxes',
            'Total',
            'Currency',
            'Payment Method',
            'Payment Form',
            'Status',
            'CFDI Use',
            'Has XML',
            'Has PDF',
            'UUID',
            'Created At',
        ];
    }

    /**
     * Get the column formats for the export
     *
     * @return array<string, string>
     */
    public function columnFormats(): array
    {
        return [
            'A' => NumberFormat::FORMAT_TEXT, // Folio
            'B' => NumberFormat::FORMAT_DATE_YYYYMMDD2, // Date
            'C' => NumberFormat::FORMAT_TEXT, // Client Name
            'D' => NumberFormat::FORMAT_TEXT, // Client RFC
            'E' => NumberFormat::FORMAT_TEXT, // Client Email
            'F' => NumberFormat::FORMAT_TEXT, // Issuer Name
            'G' => NumberFormat::FORMAT_TEXT, // Issuer RFC
            'H' => NumberFormat::FORMAT_NUMBER_COMMA_SEPARATED1, // Subtotal
            'I' => NumberFormat::FORMAT_NUMBER_COMMA_SEPARATED1, // Taxes
            'J' => NumberFormat::FORMAT_NUMBER_COMMA_SEPARATED1, // Total
            'K' => NumberFormat::FORMAT_TEXT, // Currency
            'L' => NumberFormat::FORMAT_TEXT, // Payment Method
            'M' => NumberFormat::FORMAT_TEXT, // Payment Form
            'N' => NumberFormat::FORMAT_TEXT, // Status
            'O' => NumberFormat::FORMAT_TEXT, // CFDI Use
            'P' => NumberFormat::FORMAT_TEXT, // Has XML
            'Q' => NumberFormat::FORMAT_TEXT, // Has PDF
            'R' => NumberFormat::FORMAT_TEXT, // UUID
            'S' => NumberFormat::FORMAT_DATE_DATETIME, // Created At
        ];
    }
}
