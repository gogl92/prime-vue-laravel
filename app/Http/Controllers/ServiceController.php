<?php

declare(strict_types=1);

namespace App\Http\Controllers;

use App\Models\Service;
use Illuminate\Auth\Access\Response;

class ServiceController extends BaseOrionController
{
    /**
     * Fully-qualified model class name
     */
    protected $model = Service::class;

    /**
     * Enable Orion search, filter and sort capabilities
     * @return array<int, string>
     */
    public function searchableBy(): array
    {
        return [
            'id',
            'branch_id',
            'name',
            'description',
            'price',
            'duration',
            'sku',
            'is_active',
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
            'name',
            'price',
            'duration',
            'sku',
            'is_active',
        ];
    }

    /**
     * @return array<int, string>
     */
    public function sortableBy(): array
    {
        return ['id', 'name', 'price', 'duration', 'sku', 'is_active', 'created_at', 'updated_at'];
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
     * Builds Eloquent query for fetching entities in index method.
     *
     * @param \Illuminate\Http\Request $request
     * @param array $requestedRelations
     * @return \Illuminate\Database\Eloquent\Builder
     */
    protected function buildIndexFetchQuery($request, array $requestedRelations): \Illuminate\Database\Eloquent\Builder
    {
        $query = parent::buildIndexFetchQuery($request, $requestedRelations);

        // Filter services by the authenticated user's current company branches
        $user = auth()->user();
        if ($user && $user->current_company_id) {
            $query->whereHas('branch', function ($q) use ($user) {
                $q->where('company_id', $user->current_company_id);
            });
        }

        return $query;
    }
}
