<?php

declare(strict_types=1);

namespace App\Http\Controllers;

use App\Models\Invoice;
use App\Http\Requests\StoreInvoiceRequest;
use App\Http\Requests\UpdateInvoiceRequest;
use Illuminate\Auth\Access\Response;

class InvoiceController extends BaseOrionController
{
    /**
     * Fully-qualified model class name
     */
    protected $model = Invoice::class;

    /**
     * Request classes for validation
     */
    protected string $storeRequest = StoreInvoiceRequest::class;
    protected string $updateRequest = UpdateInvoiceRequest::class;

    /**
     * Enable Orion search, filter and sort capabilities
     * @return array<int, string>
     */
    public function searchableBy(): array
    {
        return ['id', 'name', 'email', 'phone', 'address', 'city', 'state', 'zip', 'country'];
    }

    /**
     * @return array<int, string>
     */
    public function filterableBy(): array
    {
        return ['id', 'name', 'email', 'phone', 'address', 'city', 'state', 'zip', 'country'];
    }

    /**
     * @return array<int, string>
     */
    public function sortableBy(): array
    {
        return ['id', 'name', 'email', 'phone', 'address', 'city', 'state', 'zip', 'country', 'created_at', 'updated_at'];
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
    * Default pagination limit.
    *
    * @return int
    */
    public function limit(): int
    {
        return 10;
    }
}
