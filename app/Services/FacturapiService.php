<?php

declare(strict_types=1);

namespace App\Services;

use App\Contracts\MexicanInvoicingContract;
use Illuminate\Http\Client\Response;
use Illuminate\Support\Facades\Http;

class FacturapiService implements MexicanInvoicingContract
{
    protected string $apiUrl;
    protected string $apiKey;

    /**
     * Create a new FacturapiService instance
     *
     * @param string|null $apiUrl The Facturapi API URL (defaults to config value)
     * @param string|null $apiKey The API key (defaults to config value)
     *
     * @example
     * // Use config values
     * $service = new FacturapiService();
     *
     * // Use branch-specific credentials
     * $service = new FacturapiService(
     *     apiUrl: 'https://api.facturapi.io',
     *     apiKey: $branch->facturapi_api_key
     * );
     */
    public function __construct(
        ?string $apiUrl = null,
        ?string $apiKey = null
    ) {
        $this->apiUrl = $apiUrl ?? config('facturapi.api_url');
        $this->apiKey = $apiKey ?? config('facturapi.api_key');
    }

    /**
     * Create a new HTTP client instance with default configuration
     */
    protected function client(): \Illuminate\Http\Client\PendingRequest
    {
        return Http::baseUrl($this->apiUrl)
            ->withBasicAuth($this->apiKey, '')
            ->withHeaders([
                'Content-Type' => 'application/json',
                'Accept' => 'application/json',
            ])
            ->timeout(30);
    }

    /**
     * Create and seal a CFDI 4.0 invoice
     *
     * @param array $invoiceData The invoice data following Facturapi's structure
     * @return array The sealed invoice response
     */
    public function createInvoice(array $invoiceData): array
    {
        $response = $this->client()->post('/v2/invoices', $invoiceData);

        return $this->handleResponse($response);
    }

    /**
     * Cancel a previously issued invoice
     *
     * @param string $invoiceId The invoice ID to cancel
     * @param array $cancellationData Cancellation data (motive, substitution)
     * @return array The cancellation response
     */
    public function cancelInvoice(string $invoiceId, array $cancellationData = []): array
    {
        $response = $this->client()->delete("/v2/invoices/{$invoiceId}", $cancellationData);

        return $this->handleResponse($response);
    }

    /**
     * Download invoice files (XML or PDF)
     *
     * @param string $invoiceId The invoice ID
     * @param string $format The format to download (xml, pdf)
     * @return string The file content
     */
    public function downloadInvoice(string $invoiceId, string $format = 'pdf'): string
    {
        $endpoint = $format === 'xml' ? "/v2/invoices/{$invoiceId}/xml" : "/v2/invoices/{$invoiceId}/pdf";

        $response = $this->client()->get($endpoint);

        $response->throw();

        return $response->body();
    }

    /**
     * Handle the API response and throw exceptions if needed
     *
     * @param Response $response
     * @return array
     */
    protected function handleResponse(Response $response): array
    {
        $response->throw();

        return $response->json();
    }
}
