<?php

namespace Database\Factories;

use App\Models\Invoice;
use Illuminate\Database\Eloquent\Factories\Factory;

/**
 * @extends \Illuminate\Database\Eloquent\Factories\Factory<\App\Models\Payment>
 */
class PaymentFactory extends Factory
{
    /**
     * Define the model's default state.
     *
     * @return array<string, mixed>
     */
    public function definition(): array
    {
        $paymentMethods = ['credit_card', 'debit_card', 'paypal', 'bank_transfer', 'cash', 'check'];
        $statuses = ['pending', 'completed', 'failed', 'refunded'];
        $status = $this->faker->randomElement($statuses);

        return [
            'invoice_id' => Invoice::factory(),
            'amount' => $this->faker->randomFloat(2, 10, 5000),
            'payment_method' => $this->faker->randomElement($paymentMethods),
            'transaction_id' => $this->faker->uuid(),
            'status' => $status,
            'notes' => $this->faker->optional(0.3)->sentence(),
            'paid_at' => $status === 'completed' ? $this->faker->dateTimeBetween('-30 days', 'now') : null,
        ];
    }

    /**
     * Indicate that the payment is completed.
     */
    public function completed(): static
    {
        return $this->state(fn (array $attributes) => [
            'status' => 'completed',
            'paid_at' => $this->faker->dateTimeBetween('-30 days', 'now'),
        ]);
    }

    /**
     * Indicate that the payment is pending.
     */
    public function pending(): static
    {
        return $this->state(fn (array $attributes) => [
            'status' => 'pending',
            'paid_at' => null,
        ]);
    }

    /**
     * Indicate that the payment is failed.
     */
    public function failed(): static
    {
        return $this->state(fn (array $attributes) => [
            'status' => 'failed',
            'paid_at' => null,
        ]);
    }
}
