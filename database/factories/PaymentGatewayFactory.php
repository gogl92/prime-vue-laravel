<?php

namespace Database\Factories;

use Illuminate\Database\Eloquent\Factories\Factory;

/**
 * @extends \Illuminate\Database\Eloquent\Factories\Factory<\App\Models\PaymentGateway>
 */
class PaymentGatewayFactory extends Factory
{
    /**
     * Define the model's default state.
     *
     * @return array<string, mixed>
     */
    public function definition(): array
    {
        $businessName = fake()->company();
        
        return [
            'branch_id' => \App\Models\Branch::factory(),
            'is_enabled' => fake()->boolean(80),
            'slug' => \Illuminate\Support\Str::slug($businessName) . '-' . fake()->unique()->numerify('####'),
            'business_name' => $businessName,
            'logo_url' => null,
            'primary_color' => fake()->hexColor(),
            'secondary_color' => fake()->hexColor(),
            'available_product_ids' => [],
            'available_service_ids' => [],
            'available_subscription_ids' => [],
            'terms_and_conditions' => fake()->paragraph(),
            'success_message' => 'Thank you for your purchase!',
        ];
    }
}
