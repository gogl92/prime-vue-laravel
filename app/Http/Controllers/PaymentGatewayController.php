<?php

declare(strict_types=1);

namespace App\Http\Controllers;

use App\Models\PaymentGateway;
use Illuminate\Auth\Access\Response;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;

class PaymentGatewayController extends BaseOrionController
{
    /**
     * Fully-qualified model class name
     */
    protected $model = PaymentGateway::class;

    /**
     * Enable Orion search, filter and sort capabilities
     * @return array<int, string>
     */
    public function searchableBy(): array
    {
        return [
            'id',
            'branch_id',
            'slug',
            'business_name',
            'is_enabled',
        ];
    }

    /**
     * @return array<int, string>
     */
    public function filterableBy(): array
    {
        return [
            'id',
            'branch_id',
            'slug',
            'is_enabled',
        ];
    }

    /**
     * @return array<int, string>
     */
    public function sortableBy(): array
    {
        return ['id', 'slug', 'business_name', 'is_enabled', 'created_at', 'updated_at'];
    }

    /**
     * Authorize all operations for authenticated users
     * @param array $arguments
     */
    public function authorize(string $ability, $arguments = []): Response
    {
        return auth()->check() ? Response::allow() : Response::deny('Unauthorized');
    }

    /**
     * Authorize index operation
     */
    public function authorizeIndex(): bool
    {
        return auth()->check();
    }

    /**
     * Authorize store operation
     */
    public function authorizeStore(): bool
    {
        return auth()->check();
    }

    /**
     * Authorize show operation
     */
    public function authorizeShow(): bool
    {
        return auth()->check();
    }

    /**
     * Authorize update operation
     */
    public function authorizeUpdate(): bool
    {
        return auth()->check();
    }

    /**
     * Authorize destroy operation
     */
    public function authorizeDestroy(): bool
    {
        return auth()->check();
    }

    /**
     * The relations that will be included in the response.
     *
     * @return array<int, string>
     */
    public function includes(): array
    {
        return ['branch'];
    }

    /**
     * The relations that are always included in the response.
     *
     * @return array<int, string>
     */
    public function alwaysIncludes(): array
    {
        return ['branch'];
    }

    /**
     * Builds Eloquent query for fetching entities in index method.
     *
     * @param \Illuminate\Http\Request $request
     * @param array $requestedRelations
     * @return \Illuminate\Database\Eloquent\Builder
     */
    protected function buildIndexFetchQuery($request, array $requestedRelations): \Illuminate\Database\Eloquent\Builder
    {
        $query = parent::buildIndexFetchQuery($request, $requestedRelations);

        // Filter payment gateways by the authenticated user's current company branches
        $user = auth()->user();
        if ($user && $user->current_company_id) {
            $query->whereHas('branch', function ($q) use ($user) {
                $q->where('company_id', $user->current_company_id);
            });
        }

        return $query;
    }

    /**
     * Generate a new unique slug for a payment gateway
     */
    public function generateSlug(Request $request): JsonResponse
    {
        $validated = $request->validate([
            'business_name' => 'nullable|string',
        ]);

        $slug = PaymentGateway::generateUniqueSlug($validated['business_name'] ?? null);

        return response()->json(['slug' => $slug]);
    }
}

