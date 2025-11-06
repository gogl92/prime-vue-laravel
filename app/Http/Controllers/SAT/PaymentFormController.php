<?php

declare(strict_types=1);

namespace App\Http\Controllers\SAT;

use App\Models\SAT\PaymentForm;

class PaymentFormController extends BaseSATController
{
    protected $model = PaymentForm::class;

    /**
     * @return array<int, string>
     */
    public function searchableBy(): array
    {
        return ['c_forma_de_pago', 'descripcion'];
    }

    /**
     * @return array<int, string>
     */
    public function filterableBy(): array
    {
        return ['c_forma_de_pago', 'descripcion'];
    }

    /**
     * @return array<int, string>
     */
    public function sortableBy(): array
    {
        return ['c_forma_de_pago', 'descripcion'];
    }
}
