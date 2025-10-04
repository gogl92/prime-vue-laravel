<?php

declare(strict_types=1);

namespace App\Http\Controllers;

use App\Models\Invoice;

class InvoiceController extends BaseOrionController {
    /**
     * Fully-qualified model class name
     */
    protected $model = Invoice::class;

    /**
     * Authorize all operations for authenticated users
     */
    public function authorize(string $ability, $arguments = []): bool
    {
        return auth()->check();
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
}
