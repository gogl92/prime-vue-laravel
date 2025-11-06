<?php

declare(strict_types=1);

namespace App\Http\Controllers\SAT;

use App\Models\SAT\TaxRate;

class TaxRateController extends BaseSATController
{
    protected $model = TaxRate::class;

    /**
     * @return array<int, string>
     */
    public function searchableBy(): array
    {
        return ['c_impuesto', 'valor'];
    }

    /**
     * @return array<int, string>
     */
    public function filterableBy(): array
    {
        return ['c_impuesto', 'valor', 'c_tipo_factor'];
    }

    /**
     * @return array<int, string>
     */
    public function sortableBy(): array
    {
        return ['c_impuesto', 'valor'];
    }
}
