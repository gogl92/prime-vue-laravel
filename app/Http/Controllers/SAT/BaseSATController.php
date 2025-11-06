<?php

declare(strict_types=1);

namespace App\Http\Controllers\SAT;

use App\Http\Controllers\BaseOrionController;
use Illuminate\Auth\Access\Response;

abstract class BaseSATController extends BaseOrionController
{
    /**
     * Authorize all operations for authenticated users
     * @param array<mixed> $arguments
     */
    public function authorize(string $ability, $arguments = []): Response
    {
        return auth()->check() ? Response::allow() : Response::deny('Unauthorized');
    }

    /**
     * Allow index operation
     */
    public function authorizeIndex(): bool
    {
        return auth()->check();
    }

    /**
     * Disable store operation (read-only catalogs)
     */
    public function authorizeStore(): bool
    {
        return false;
    }

    /**
     * Allow show operation
     */
    public function authorizeShow(): bool
    {
        return auth()->check();
    }

    /**
     * Disable update operation (read-only catalogs)
     */
    public function authorizeUpdate(): bool
    {
        return false;
    }

    /**
     * Disable destroy operation (read-only catalogs)
     */
    public function authorizeDestroy(): bool
    {
        return false;
    }

    /**
     * Default pagination limit
     */
    public function limit(): int
    {
        return 50;
    }
}
