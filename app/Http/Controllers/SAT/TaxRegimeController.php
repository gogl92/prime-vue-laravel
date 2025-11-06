<?php

declare(strict_types=1);

namespace App\Http\Controllers\SAT;

use App\Models\SAT\TaxRegime;

class TaxRegimeController extends BaseSATController
{
    protected $model = TaxRegime::class;

    /**
     * @return array<int, string>
     */
    public function searchableBy(): array
    {
        return ['c_regimen_fiscal', 'descripcion'];
    }

    /**
     * @return array<int, string>
     */
    public function filterableBy(): array
    {
        return ['c_regimen_fiscal', 'descripcion', 'aplica_tipo_persona_fisica', 'aplica_tipo_persona_moral'];
    }

    /**
     * @return array<int, string>
     */
    public function sortableBy(): array
    {
        return ['c_regimen_fiscal', 'descripcion'];
    }
}
