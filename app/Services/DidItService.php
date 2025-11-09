<?php

declare(strict_types=1);

namespace App\Services;

use Illuminate\Http\Client\ConnectionException;
use Illuminate\Http\Client\PendingRequest;
use Illuminate\Http\Client\RequestException;
use Illuminate\Support\Facades\Http;
use App\Contracts\FacialRecognitionContract;
use InvalidArgumentException;

class DidItService implements FacialRecognitionContract
{
    protected string $apiUrl;
    protected string $apiKey;
    protected string $apiId;

    public function __construct()
    {
        $this->apiUrl = config('didit.api_url');
        $this->apiKey = config('didit.api_key');
        $this->apiId = config('didit.api_id');
    }

    /**
     * Get an authenticated HTTP client instance.
     */
    protected function client(): PendingRequest
    {
        return Http::baseUrl($this->apiUrl)
            ->withHeaders([
                'x-api-key' => $this->apiKey,
                'x-app-id' => $this->apiId,
                'Accept' => 'application/json',
                'Content-Type' => 'application/json',
            ])
            ->timeout(30);
    }

    /**
     * @throws RequestException
     * @throws ConnectionException
     */
    public function verify(string $baseImagePath, string $faceImagePath): array
    {
        // Validate that files exist
        if (!file_exists($baseImagePath)) {
            throw new InvalidArgumentException("Base image file not found: {$baseImagePath}");
        }

        if (!file_exists($faceImagePath)) {
            throw new InvalidArgumentException("Face image file not found: {$faceImagePath}");
        }

        // Determine MIME types
        $baseMimeType = mime_content_type($baseImagePath) ?: 'image/jpeg';
        $faceMimeType = mime_content_type($faceImagePath) ?: 'image/jpeg';

        // Make multipart request - Don't use client() method because it sets Content-Type to application/json
        // which conflicts with multipart/form-data
        $response = Http::baseUrl($this->apiUrl)
            ->withHeaders([
                'x-api-key' => $this->apiKey,
                'accept' => 'application/json',
                // Do NOT set Content-Type here - Laravel will auto-set it to multipart/form-data
            ])
            ->timeout(30)
            ->attach(
                'ref_image',
                file_get_contents($baseImagePath),
                basename($baseImagePath),
                ['Content-Type' => $baseMimeType]
            )
            ->attach(
                'user_image',
                file_get_contents($faceImagePath),
                basename($faceImagePath),
                ['Content-Type' => $faceMimeType]
            )
            ->post('/v2/face-match/');

        $response = $response->throw()->json();

        logger()->info('DidIt API Response', $response);

        return $response;
    }
}
