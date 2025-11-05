<?php

declare(strict_types=1);

namespace App\Http\Controllers;

use App\Http\Requests\StoreCompanyRequest;
use App\Http\Requests\UpdateCompanyRequest;
use App\Models\Company;
use Illuminate\Auth\Access\Response;

class CompanyController extends BaseOrionController
{
    /**
     * Fully-qualified model class name
     */
    protected $model = Company::class;

    /**
     * Store request class
     */
    protected string $storeRequest = StoreCompanyRequest::class;

    /**
     * Update request class
     */
    protected string $updateRequest = UpdateCompanyRequest::class;

    /**
     * Enable Orion search, filter and sort capabilities
     * @return array<int, string>
     */
    public function searchableBy(): array
    {
        return [
            'id',
            'name',
            'street_1',
            'street_2',
            'city',
            'state',
            'zip',
            'country',
            'phone',
            'email',
            'tax_id',
            'tax_name',
        ];
    }

    /**
     * @return array<int, string>
     */
    public function filterableBy(): array
    {
        return [
            'id',
            'name',
            'city',
            'state',
            'country',
            'email',
            'tax_id',
            'created_at',
            'updated_at',
        ];
    }

    /**
     * @return array<int, string>
     */
    public function sortableBy(): array
    {
        return [
            'id',
            'name',
            'city',
            'state',
            'country',
            'created_at',
            'updated_at',
        ];
    }

    /**
     * Authorize all operations for authenticated users
     * @param array<mixed> $arguments
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
     * @return array<string>
     */
    public function includes(): array
    {
        return [
            'creator',
            'updater',
            'deleter',
        ];
    }
}
