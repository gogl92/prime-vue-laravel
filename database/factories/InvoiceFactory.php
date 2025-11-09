<?php

declare(strict_types=1);

namespace Database\Factories;

use App\Models\Client;
use App\Models\Invoice;
use Illuminate\Database\Eloquent\Factories\Factory;

/**
 * @extends Factory<Invoice>
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
        $receiptTypes = ['Factura', 'Nota de Crédito', 'Nota de Débito'];
        $statuses = ['pending', 'approved', 'rejected'];

        return [
            'client_id' => Client::factory(),
            'issuer_id' => Client::factory()->issuer(),
            'cfdi_type' => $this->faker->randomElement($cfdiTypes),
            'order_number' => $this->faker->unique()->numerify('ORD-#####'),
            'date' => $this->faker->dateTimeBetween('-1 year', 'now'),
            'payment_form' => $this->faker->randomElement($paymentForms),
            'send_email' => $this->faker->boolean(80), // 80% send email
            'payment_method' => $this->faker->randomElement($paymentMethods),
            'cfdi_use' => $this->faker->randomElement($cfdiUses),
            'series' => $this->faker->randomElement($series),
            'exchange_rate' => $this->faker->randomFloat(4, 1, 25),
            'import' => $this->faker->randomFloat(2, 100, 10000),
            'import_usd' => $this->faker->randomFloat(2, 100, 10000),
            'sub_total' => $this->faker->randomFloat(2, 100, 10000),
            'retention_tax' => $this->faker->randomFloat(2, 100, 10000),
            'iva_tax' => $this->faker->randomFloat(2, 100, 10000),
            'paid' => $this->faker->randomFloat(2, 100, 10000),
            'sender_name' => $this->faker->name(),
            'sender_rfc' => $this->faker->regexify('/^[A-Z0-9]{13}$/'),
            'receipt_rfc' => $this->faker->regexify('/^[A-Z0-9]{13}$/'),
            'receipt_type' => $this->faker->randomElement($receiptTypes),
            'complement_id' => $this->faker->unique()->numerify('COMP-#####'),
            'complement_date' => $this->faker->dateTimeBetween('-1 year', 'now'),
            'pdf' => $this->faker->imageUrl(),
            'xml_path' => $this->faker->imageUrl(),
            'cfdi_json' => json_encode([]),
            'comments' => $this->faker->optional(0.3)->paragraph(),
            'expenses_type_id' => null,
            'status' => $this->faker->randomElement($statuses),
            'token' => $this->faker->uuid(),
            'currency' => $this->faker->randomElement($currencies),
        ];
    }
}
