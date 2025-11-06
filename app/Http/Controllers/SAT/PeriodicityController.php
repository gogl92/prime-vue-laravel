<?php

declare(strict_types=1);

namespace App\Http\Controllers\SAT;

use App\Models\SAT\Periodicity;

class PeriodicityController extends BaseSATController
{
    protected $model = Periodicity::class;

    /**
     * @return array<int, string>
     */
    public function searchableBy(): array
    {
        return ['c_periodicidad', 'descripcion'];
    }

    /**
     * @return array<int, string>
     */
    public function filterableBy(): array
    {
        return ['c_periodicidad', 'descripcion'];
    }

    /**
     * @return array<int, string>
     */
    public function sortableBy(): array
    {
        return ['c_periodicidad', 'descripcion'];
    }
}
