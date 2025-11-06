<?php

declare(strict_types=1);

namespace App\Http\Controllers\SAT;

use App\Models\SAT\RelationType;

class RelationTypeController extends BaseSATController
{
    protected $model = RelationType::class;

    /**
     * @return array<int, string>
     */
    public function searchableBy(): array
    {
        return ['c_tipo_de_relacion', 'descripcion'];
    }

    /**
     * @return array<int, string>
     */
    public function filterableBy(): array
    {
        return ['c_tipo_de_relacion', 'descripcion'];
    }

    /**
     * @return array<int, string>
     */
    public function sortableBy(): array
    {
        return ['c_tipo_de_relacion', 'descripcion'];
    }
}
