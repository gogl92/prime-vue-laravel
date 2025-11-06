<?php

declare(strict_types=1);

namespace App\Http\Controllers\SAT;

use App\Models\SAT\PaymentMethod;

class PaymentMethodController extends BaseSATController
{
    protected $model = PaymentMethod::class;

    /**
     * @return array<int, string>
     */
    public function searchableBy(): array
    {
        return ['c_metodo_de_pago', 'descripcion'];
    }

    /**
     * @return array<int, string>
     */
    public function filterableBy(): array
    {
        return ['c_metodo_de_pago', 'descripcion'];
    }

    /**
     * @return array<int, string>
     */
    public function sortableBy(): array
    {
        return ['c_metodo_de_pago', 'descripcion'];
    }
}
