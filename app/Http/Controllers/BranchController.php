<?php

declare(strict_types=1);

namespace App\Http\Controllers;

use App\Models\Branch;
use App\Http\Requests\StoreBranchRequest;
use App\Http\Requests\UpdateBranchRequest;
use Illuminate\Auth\Access\Response;

class BranchController extends BaseOrionController
{
    /**
     * Fully-qualified model class name
     */
    protected $model = Branch::class;

    /**
     * Request classes for validation
     */
    protected string $storeRequest = StoreBranchRequest::class;
    protected string $updateRequest = UpdateBranchRequest::class;

    /**
     * Enable Orion search, filter and sort capabilities
     * @return array<int, string>
     */
    public function searchableBy(): array
    {
        return [
            'id',
            'company_id',
            'name',
            'code',
            'email',
            'phone',
            'address',
            'city',
            'state',
            'zip',
            'country',
            'is_active',
            'description',
        ];
    }

    /**
     * @return array<int, string>
     */
    public function filterableBy(): array
    {
        return [
            'id',
            'company_id',
            'name',
            'code',
            'email',
            'phone',
            'address',
            'city',
            'state',
            'zip',
            'country',
            'is_active',
        ];
    }

    /**
     * @return array<int, string>
     */
    public function sortableBy(): array
    {
        return ['id', 'name', 'code', 'email', 'phone', 'city', 'state', 'country', 'is_active', 'created_at', 'updated_at'];
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
        return ['stripeAccountMapping'];
    }
}
