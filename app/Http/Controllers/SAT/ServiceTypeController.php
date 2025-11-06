<?php

declare(strict_types=1);

namespace App\Http\Controllers\SAT;

use App\Models\SAT\ServiceType;

class ServiceTypeController extends BaseSATController
{
    protected $model = ServiceType::class;

    /**
     * @return array<int, string>
     */
    public function searchableBy(): array
    {
        return ['c_tipo_de_serv', 'descripcion'];
    }

    /**
     * @return array<int, string>
     */
    public function filterableBy(): array
    {
        return ['c_tipo_de_serv', 'descripcion'];
    }

    /**
     * @return array<int, string>
     */
    public function sortableBy(): array
    {
        return ['c_tipo_de_serv', 'descripcion'];
    }
}
