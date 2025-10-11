<?php

declare(strict_types=1);

namespace App\Http\Controllers;

use App\Models\Payment;
use Illuminate\Auth\Access\Response;

class InvoicePaymentsController extends BaseOrionController
{
    /**
     * Fully-qualified model class name
     */
    protected $model = Payment::class;

    /**
     * Enable Orion search, filter and sort capabilities
     * @return array<int, string>
     */
    public function searchableBy(): array
    {
        return ['id', 'transaction_id', 'payment_method', 'status', 'invoice_id'];
    }

    /**
     * @return array<int, string>
     */
    public function filterableBy(): array
    {
        return ['id', 'amount', 'payment_method', 'status', 'transaction_id', 'paid_at', 'invoice_id'];
    }

    /**
     * @return array<int, string>
     */
    public function sortableBy(): array
    {
        return ['id', 'amount', 'payment_method', 'status', 'paid_at', 'created_at', 'updated_at', 'invoice_id'];
    }

    /**
     * Enable including relations
     * @return array<int, string>
     */
    public function includes(): array
    {
        return ['invoice'];
    }

    /**
     * Authorize all operations for authenticated users
     * @param array<mixed> $arguments
     */
    public function authorize(string $ability, $arguments = []): Response
    {
        // @phpstan-ignore-next-line
        return auth()->check() ? Response::allow() : Response::deny('Unauthorized');
    }

    /**
     * Authorize index operation
     */
    public function authorizeIndex(): bool
    {
        // @phpstan-ignore-next-line
        return auth()->check();
    }

    /**
     * Authorize store operation
     */
    public function authorizeStore(): bool
    {
        // @phpstan-ignore-next-line
        return auth()->check();
    }

    /**
     * Authorize show operation
     */
    public function authorizeShow(): bool
    {
        // @phpstan-ignore-next-line
        return auth()->check();
    }

    /**
     * Authorize update operation
     */
    public function authorizeUpdate(): bool
    {
        // @phpstan-ignore-next-line
        return auth()->check();
    }

    /**
     * Authorize destroy operation
     */
    public function authorizeDestroy(): bool
    {
        // @phpstan-ignore-next-line
        return auth()->check();
    }
}

