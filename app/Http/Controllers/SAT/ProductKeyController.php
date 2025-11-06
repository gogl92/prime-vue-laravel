<?php

declare(strict_types=1);

namespace App\Http\Controllers\SAT;

use App\Models\SAT\ProductKey;

class ProductKeyController extends BaseSATController
{
    protected $model = ProductKey::class;

    /**
     * @return array<int, string>
     */
    public function searchableBy(): array
    {
        return ['c_producto_servicio', 'descripcion', 'tipo'];
    }

    /**
     * @return array<int, string>
     */
    public function filterableBy(): array
    {
        return ['c_producto_servicio', 'descripcion', 'tipo', 'division'];
    }

    /**
     * @return array<int, string>
     */
    public function sortableBy(): array
    {
        return ['c_producto_servicio', 'descripcion', 'tipo'];
    }
}
