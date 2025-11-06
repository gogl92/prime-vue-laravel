<?php

declare(strict_types=1);

namespace App\Http\Controllers\SAT;

use App\Models\SAT\TaxRateAmount;

class TaxRateAmountController extends BaseSATController
{
    protected $model = TaxRateAmount::class;

    /**
     * @return array<int, string>
     */
    public function searchableBy(): array
    {
        return ['valor', 'impuesto'];
    }

    /**
     * @return array<int, string>
     */
    public function filterableBy(): array
    {
        return ['valor', 'impuesto'];
    }

    /**
     * @return array<int, string>
     */
    public function sortableBy(): array
    {
        return ['valor', 'impuesto'];
    }
}
