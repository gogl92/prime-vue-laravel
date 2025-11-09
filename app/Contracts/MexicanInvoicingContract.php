<?php

declare(strict_types=1);

namespace App\Contracts;

interface MexicanInvoicingContract
{
    /**
     * Create and seal a CFDI invoice
     *
     * @param array $invoiceData The invoice data to be sent to the provider
     * @return array The sealed invoice response from the provider
     */
    public function createInvoice(array $invoiceData): array;

    /**
     * Cancel a previously issued invoice
     *
     * @param string $invoiceId The invoice ID to cancel
     * @param array $cancellationData Additional cancellation data (motivo, uuid_sustitucion, etc.)
     * @return array The cancellation response
     */
    public function cancelInvoice(string $invoiceId, array $cancellationData = []): array;

    /**
     * Download invoice files (XML, PDF)
     *
     * @param string $invoiceId The invoice ID
     * @param string $format The format to download (xml, pdf)
     * @return string The file content
     */
    public function downloadInvoice(string $invoiceId, string $format = 'pdf'): string;
}
