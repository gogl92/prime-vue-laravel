<?php

declare(strict_types=1);

namespace App\Http\Controllers;

use App\Http\Requests\StoreCustomerRequest;
use App\Http\Requests\UpdateCustomerRequest;
use App\Models\Customer;
use Illuminate\Auth\Access\Response;

class CustomerController extends BaseOrionController
{
    /**
     * Fully-qualified model class name
     */
    protected $model = Customer::class;

    /**
     * Store request class
     */
    protected $storeRequest = StoreCustomerRequest::class;

    /**
     * Update request class
     */
    protected $updateRequest = UpdateCustomerRequest::class;

    /**
     * Enable Orion search, filter and sort capabilities
     * @return array<int, string>
     */
    public function searchableBy(): array
    {
        return [
            'id',
            'first_name',
            'last_name',
            'email',
            'phone',
            'street_1',
            'street_2',
            'city',
            'state',
            'zip',
            'country',
            'notes',
        ];
    }

    /**
     * @return array<int, string>
     */
    public function filterableBy(): array
    {
        return [
            'id',
            'first_name',
            'last_name',
            'email',
            'city',
            'state',
            'country',
            'is_active',
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
            'first_name',
            'last_name',
            'email',
            'city',
            'state',
            'country',
            'is_active',
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
