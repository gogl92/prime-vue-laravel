<?php

declare(strict_types=1);

namespace App\Http\Controllers\SAT;

use App\Models\SAT\Country;

class CountryController extends BaseSATController
{
    protected $model = Country::class;

    /**
     * @return array<int, string>
     */
    public function searchableBy(): array
    {
        return ['c_pais', 'descripcion'];
    }

    /**
     * @return array<int, string>
     */
    public function filterableBy(): array
    {
        return ['c_pais', 'descripcion'];
    }

    /**
     * @return array<int, string>
     */
    public function sortableBy(): array
    {
        return ['c_pais', 'descripcion'];
    }
}
