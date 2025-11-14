<?php

declare(strict_types=1);

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Models\Branch;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Validation\Rule;
use Illuminate\Validation\ValidationException;

class BranchInvoiceSettingsController extends Controller
{
    /**
     * Get invoice settings for a branch
     *
     * @param int $id Branch ID
     * @return JsonResponse
     */
    public function show(int $id): JsonResponse
    {
        $branch = Branch::findOrFail($id);

        // Check authorization
        if (!auth()->check()) {
            return response()->json([
                'message' => 'Unauthorized',
            ], 401);
        }

        // Get all invoice-related settings
        $settings = [
            'invoicing_provider' => $branch->settings()->get('invoicing_provider', 'facturacom'),

            // Facturacom settings
            'facturacom_api_url' => $branch->settings()->get('facturacom_api_url'),
            'facturacom_api_key' => $branch->settings()->get('facturacom_api_key') ? '••••••••' : null,
            'facturacom_secret_key' => $branch->settings()->get('facturacom_secret_key') ? '••••••••' : null,
            'facturacom_plugin_key' => $branch->settings()->get('facturacom_plugin_key') ? '••••••••' : null,
            'facturacom_has_credentials' => (bool) $branch->settings()->get('facturacom_api_key'),

            // Facturapi settings
            'facturapi_api_url' => $branch->settings()->get('facturapi_api_url'),
            'facturapi_api_key' => $branch->settings()->get('facturapi_api_key') ? '••••••••' : null,
            'facturapi_has_credentials' => (bool) $branch->settings()->get('facturapi_api_key'),
        ];

        return response()->json($settings);
    }

    /**
     * Update invoice settings for a branch
     *
     * @param Request $request
     * @param int $id Branch ID
     * @return JsonResponse
     * @throws ValidationException
     */
    public function update(Request $request, int $id): JsonResponse
    {
        $branch = Branch::findOrFail($id);

        // Check authorization
        if (!auth()->check()) {
            return response()->json([
                'message' => 'Unauthorized',
            ], 401);
        }

        $validated = $request->validate([
            'invoicing_provider' => ['sometimes', 'nullable', Rule::in(['facturacom', 'facturapi'])],

            // Facturacom credentials
            'facturacom_api_url' => ['sometimes', 'nullable', 'url'],
            'facturacom_api_key' => ['sometimes', 'nullable', 'string'],
            'facturacom_secret_key' => ['sometimes', 'nullable', 'string'],
            'facturacom_plugin_key' => ['sometimes', 'nullable', 'string'],

            // Facturapi credentials
            'facturapi_api_url' => ['sometimes', 'nullable', 'url'],
            'facturapi_api_key' => ['sometimes', 'nullable', 'string'],
        ]);

        // Update each setting if provided
        foreach ($validated as $key => $value) {
            // Only update if value is not the masked string (••••••••)
            if ($value !== null && $value !== '••••••••') {
                $branch->settings()->set($key, $value);
            }
        }

        return response()->json([
            'message' => 'Invoice settings updated successfully',
            'settings' => $this->show($id)->getData(),
        ]);
    }

    /**
     * Delete specific credentials for a branch
     *
     * @param Request $request
     * @param int $id Branch ID
     * @return JsonResponse
     * @throws ValidationException
     */
    public function destroy(Request $request, int $id): JsonResponse
    {
        $branch = Branch::findOrFail($id);

        // Check authorization
        if (!auth()->check()) {
            return response()->json([
                'message' => 'Unauthorized',
            ], 401);
        }

        $validated = $request->validate([
            'provider' => ['required', Rule::in(['facturacom', 'facturapi'])],
        ]);

        $provider = $validated['provider'];

        // Delete credentials based on provider
        if ($provider === 'facturacom') {
            $branch->settings()->forget([
                'facturacom_api_url',
                'facturacom_api_key',
                'facturacom_secret_key',
                'facturacom_plugin_key',
            ]);
        } elseif ($provider === 'facturapi') {
            $branch->settings()->forget([
                'facturapi_api_url',
                'facturapi_api_key',
            ]);
        }

        return response()->json([
            'message' => ucfirst($provider) . ' credentials deleted successfully',
        ]);
    }
}
