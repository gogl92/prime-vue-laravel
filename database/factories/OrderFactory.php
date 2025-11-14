<?php

namespace Database\Factories;

use Illuminate\Database\Eloquent\Factories\Factory;

/**
 * @extends \Illuminate\Database\Eloquent\Factories\Factory<\App\Models\Order>
 */
class OrderFactory extends Factory
{
    /**
     * Define the model's default state.
     *
     * @return array<string, mixed>
     */
    public function definition(): array
    {
        return [
            'order_number' => 'ORD-' . strtoupper(fake()->unique()->bothify('????????')),
            'branch_id' => \App\Models\Branch::factory(),
            'payment_gateway_id' => \App\Models\PaymentGateway::factory(),
            'customer_email' => fake()->safeEmail(),
            'customer_name' => fake()->name(),
            'customer_phone' => fake()->phoneNumber(),
            'total_amount' => fake()->randomFloat(2, 10, 1000),
            'currency' => 'USD',
            'status' => fake()->randomElement(['pending', 'completed', 'failed', 'refunded']),
            'stripe_payment_intent_id' => 'pi_' . fake()->bothify('??##################'),
            'stripe_charge_id' => 'ch_' . fake()->bothify('??##################'),
            'items' => [
                [
                    'type' => 'product',
                    'id' => 1,
                    'name' => fake()->words(3, true),
                    'price' => 50.00,
                    'quantity' => 2,
                ],
            ],
            'customer_notes' => fake()->optional()->sentence(),
            'completed_at' => fake()->optional()->dateTimeBetween('-1 month', 'now'),
        ];
    }
}
