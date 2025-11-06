<?php

declare(strict_types=1);

namespace App\Http\Controllers\SAT;

use App\Models\SAT\WithholdingTax;

class WithholdingTaxController extends BaseSATController
{
    protected $model = WithholdingTax::class;

    /**
     * @return array<int, string>
     */
    public function searchableBy(): array
    {
        return ['c_retenciones', 'descripcion'];
    }

    /**
     * @return array<int, string>
     */
    public function filterableBy(): array
    {
        return ['c_retenciones', 'descripcion'];
    }

    /**
     * @return array<int, string>
     */
    public function sortableBy(): array
    {
        return ['c_retenciones', 'descripcion'];
    }
}
