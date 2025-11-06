<?php

declare(strict_types=1);

namespace App\Http\Controllers;

use App\Models\Product;
use App\Http\Requests\StoreProductRequest;
use App\Http\Requests\UpdateProductRequest;
use Illuminate\Auth\Access\Response;

class ProductController extends BaseOrionController
{
    /**
     * Fully-qualified model class name
     */
    protected $model = Product::class;

    /**
     * Request classes for validation
     */
    protected string $storeRequest = StoreProductRequest::class;
    protected string $updateRequest = UpdateProductRequest::class;

    /**
     * Enable Orion search, filter and sort capabilities
     * @return array<int, string>
     */
    public function searchableBy(): array
    {
        return [
            'id',
            'invoices.id',
            'name',
            'description',
            'sku',
            'clave_prod_serv',
            'unidad',
            'numero_pedimento',
            'cuenta_predial',
        ];
    }

    /**
     * @return array<int, string>
     */
    public function filterableBy(): array
    {
        return [
            'id',
            'invoices.id',
            'name',
            'description',
            'price',
            'quantity',
            'sku',
            'clave_prod_serv',
            'clave_unidad',
            'status',
        ];
    }

    /**
     * @return array<int, string>
     */
    public function sortableBy(): array
    {
        return [
            'id',
            'invoices.id',
            'name',
            'description',
            'price',
            'quantity',
            'sku',
            'clave_prod_serv',
            'unidad',
            'importe',
            'descuento',
            'status',
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
}
