<?php

declare(strict_types=1);

namespace App\Http\Controllers\SAT;

use App\Models\SAT\PostalCode;

class PostalCodeController extends BaseSATController
{
    protected $model = PostalCode::class;

    /**
     * @return array<int, string>
     */
    public function searchableBy(): array
    {
        return ['codigo_postal', 'estado', 'municipio'];
    }

    /**
     * @return array<int, string>
     */
    public function filterableBy(): array
    {
        return ['codigo_postal', 'estado', 'municipio'];
    }

    /**
     * @return array<int, string>
     */
    public function sortableBy(): array
    {
        return ['codigo_postal', 'estado'];
    }
}
