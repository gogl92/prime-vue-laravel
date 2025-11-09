<?php

declare(strict_types=1);

namespace App\Services;

use App\Contracts\MexicanInvoicingContract;
use Illuminate\Http\Client\Response;
use Illuminate\Support\Facades\Http;

class FacturacomService implements MexicanInvoicingContract
{
    protected string $apiUrl;
    protected string $apiKey;
    protected string $secretKey;
    protected string $pluginKey;

    public function __construct()
    {
        $this->apiUrl = config('facturacom.api_url');
        $this->apiKey = config('facturacom.api_key');
        $this->secretKey = config('facturacom.secret_key');
        $this->pluginKey = config('facturacom.plugin_key');
    }

    /**
     * Create a new HTTP client instance with default configuration
     */
    protected function client(): \Illuminate\Http\Client\PendingRequest
    {
        return Http::baseUrl($this->apiUrl)
            ->withHeaders([
                'F-PLUGIN' => $this->pluginKey,
                'F-Api-Key' => $this->apiKey,
                'F-Secret-Key' => $this->secretKey,
                'Content-Type' => 'application/json',
                'Accept' => 'application/json',
            ])
            ->timeout(30);
    }

    /**
     * Create and seal a CFDI 4.0 invoice
     *
     * @param array $invoiceData The invoice data following Facturacom's structure
     * @return array The sealed invoice response
     */
    public function createInvoice(array $invoiceData): array
    {
        $response = $this->client()->post('/v4/cfdi40/create', $invoiceData);

        return $this->handleResponse($response);
    }

    /**
     * Cancel a previously issued invoice
     *
     * @param string $invoiceId The invoice UUID to cancel
     * @param array $cancellationData Cancellation data (motivo, uuid_sustitucion)
     * @return array The cancellation response
     */
    public function cancelInvoice(string $invoiceId, array $cancellationData = []): array
    {
        $response = $this->client()->post("/v4/cfdi40/{$invoiceId}/cancel", $cancellationData);

        return $this->handleResponse($response);
    }

    /**
     * Download invoice files (XML or PDF)
     *
     * @param string $invoiceId The invoice UUID
     * @param string $format The format to download (xml, pdf)
     * @return string The file content
     */
    public function downloadInvoice(string $invoiceId, string $format = 'pdf'): string
    {
        $response = $this->client()->get("/v4/cfdi40/{$invoiceId}/{$format}");

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
