<?php

declare(strict_types=1);

namespace App\Http\Controllers\SAT;

use App\Models\SAT\ServiceSubtype;

class ServiceSubtypeController extends BaseSATController
{
    protected $model = ServiceSubtype::class;

    /**
     * @return array<int, string>
     */
    public function searchableBy(): array
    {
        return ['c_sub_tipo_serv', 'descripcion'];
    }

    /**
     * @return array<int, string>
     */
    public function filterableBy(): array
    {
        return ['c_sub_tipo_serv', 'descripcion', 'tipo_de_servicio'];
    }

    /**
     * @return array<int, string>
     */
    public function sortableBy(): array
    {
        return ['c_sub_tipo_serv', 'descripcion'];
    }
}
