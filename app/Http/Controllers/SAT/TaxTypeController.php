<?php

declare(strict_types=1);

namespace App\Http\Controllers\SAT;

use App\Models\SAT\TaxType;

class TaxTypeController extends BaseSATController
{
    protected $model = TaxType::class;

    /**
     * @return array<int, string>
     */
    public function searchableBy(): array
    {
        return ['c_impuesto', 'descripcion'];
    }

    /**
     * @return array<int, string>
     */
    public function filterableBy(): array
    {
        return ['c_impuesto', 'descripcion'];
    }

    /**
     * @return array<int, string>
     */
    public function sortableBy(): array
    {
        return ['c_impuesto', 'descripcion'];
    }
}
