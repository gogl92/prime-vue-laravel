<?php

declare(strict_types=1);

namespace App\Services;

use App\Contracts\MexicanInvoicingContract;
use Illuminate\Http\Client\ConnectionException;
use Illuminate\Http\Client\PendingRequest;
use Illuminate\Http\Client\Response;
use Illuminate\Support\Facades\Http;

class FacturacomService implements MexicanInvoicingContract
{
    protected string $apiUrl;

    protected string $apiKey;

    protected string $secretKey;

    protected string $pluginKey;

    /**
     * Create a new FacturacomService instance
     *
     * @param  string|null  $apiUrl  The Facturacom API URL (defaults to config value)
     * @param  string|null  $apiKey  The API key (defaults to config value)
     * @param  string|null  $secretKey  The secret key (defaults to config value)
     * @param  string|null  $pluginKey  The plugin key (defaults to config value)
     *
     * @example
     * // Use config values
     * $service = new FacturacomService();
     *
     * // Use branch-specific credentials
     * $service = new FacturacomService(
     *     apiUrl: 'https://factura.com/api',
     *     apiKey: $branch->facturacion_api_key,
     *     secretKey: $branch->facturacion_secret_key,
     *     pluginKey: $branch->facturacion_plugin_key
     * );
     */
    public function __construct(
        ?string $apiUrl = null,
        ?string $apiKey = null,
        ?string $secretKey = null,
        ?string $pluginKey = null
    ) {
        $this->apiUrl = $apiUrl ?? config('facturacom.api_url');
        $this->apiKey = $apiKey ?? config('facturacom.api_key');
        $this->secretKey = $secretKey ?? config('facturacom.secret_key');
        $this->pluginKey = $pluginKey ?? config('facturacom.plugin_key');
    }

    /**
     * Create a new HTTP client instance with the default configuration
     */
    protected function client(): PendingRequest
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
     * @param  array  $invoiceData  The invoice data following Facturacom's structure
     * @return array The sealed invoice response
     *
     * @throws ConnectionException
     */
    public function createInvoice(array $invoiceData): array
    {
        $response = $this->client()->post('/v4/cfdi40/create', $invoiceData);

        return $this->handleResponse($response);
    }

    /**
     * Cancel a previously issued invoice
     *
     * @param  string  $invoiceId  The invoice UUID to cancel
     * @param  array  $cancellationData  Cancellation data (motivo, uuid_sustitucion)
     * @return array The cancellation response
     *
     * @throws ConnectionException
     */
    public function cancelInvoice(string $invoiceId, array $cancellationData = []): array
    {
        $response = $this->client()->post("/v4/cfdi40/$invoiceId/cancel", $cancellationData);

        return $this->handleResponse($response);
    }

    /**
     * Download invoice files (XML or PDF)
     *
     * @param  string  $invoiceId  The invoice UUID
     * @param  string  $format  The format to download (xml, pdf)
     * @return string The file content
     */
    public function downloadInvoice(string $invoiceId, string $format = 'pdf'): string
    {
        $response = $this->client()->get("/v4/cfdi40/$invoiceId/$format");

        $response->throw();

        return $response->body();
    }

    /**
     * Handle the API response and throw exceptions if needed
     */
    protected function handleResponse(Response $response): array
    {
        $response->throw();

        return $response->json();
    }
}
