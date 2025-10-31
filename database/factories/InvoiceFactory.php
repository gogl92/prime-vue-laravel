<?php

declare(strict_types=1);

namespace Database\Factories;

use App\Models\Client;
use Illuminate\Database\Eloquent\Factories\Factory;

/**
 * @extends \Illuminate\Database\Eloquent\Factories\Factory<\App\Models\Invoice>
 */
class InvoiceFactory extends Factory
{
    /**
     * Define the model's default state.
     *
     * @return array<string, mixed>
     */
    public function definition(): array
    {
        $cfdiTypes = ['Factura', 'Nota de Crédito', 'Nota de Débito'];
        $paymentForms = [
            'Transferencia electrónica de fondos',
            'Efectivo',
            'Cheque',
            'Tarjeta de crédito',
            'Tarjeta de débito'
        ];
        $paymentMethods = [
            'Pago en parcialidades o diferido',
            'Pago en una sola exhibición'
        ];
        $cfdiUses = [
            'Adquisición de mercancias',
            'Servicios profesionales',
            'Servicios de hospedaje',
            'Otros'
        ];
        $series = ['F', 'A', 'B', 'C'];
        $currencies = ['MXN', 'USD', 'EUR'];

        return [
            'client_id' => Client::factory(),
            'issuer_id' => Client::factory()->issuer(),
            'cfdi_type' => $this->faker->randomElement($cfdiTypes),
            'order_number' => $this->faker->unique()->numerify('ORD-#####'),
            'invoice_date' => $this->faker->dateTimeBetween('-1 year', 'now'),
            'payment_form' => $this->faker->randomElement($paymentForms),
            'send_email' => $this->faker->boolean(80), // 80% send email
            'payment_method' => $this->faker->randomElement($paymentMethods),
            'cfdi_use' => $this->faker->randomElement($cfdiUses),
            'series' => $this->faker->randomElement($series),
            'exchange_rate' => $this->faker->randomFloat(4, 1, 25),
            'currency' => $this->faker->randomElement($currencies),
            'comments' => $this->faker->optional(0.3)->paragraph(),
        ];
    }
}
