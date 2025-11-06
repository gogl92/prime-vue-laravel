<?php

declare(strict_types=1);

namespace App\Http\Controllers\SAT;

use App\Models\SAT\Currency;

class CurrencyController extends BaseSATController
{
    protected $model = Currency::class;

    /**
     * @return array<int, string>
     */
    public function searchableBy(): array
    {
        return ['c_moneda', 'descripcion'];
    }

    /**
     * @return array<int, string>
     */
    public function filterableBy(): array
    {
        return ['c_moneda', 'descripcion'];
    }

    /**
     * @return array<int, string>
     */
    public function sortableBy(): array
    {
        return ['c_moneda', 'descripcion'];
    }
}
