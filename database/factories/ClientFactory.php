<?php

namespace Database\Factories;

use Illuminate\Database\Eloquent\Factories\Factory;

/**
 * @extends \Illuminate\Database\Eloquent\Factories\Factory<\App\Models\Client>
 */
class ClientFactory extends Factory
{
    /**
     * Define the model's default state.
     *
     * @return array<string, mixed>
     */
    public function definition(): array
    {
        return [
            'name' => fake()->company(),
            'email' => fake()->unique()->companyEmail(),
            'phone' => fake()->phoneNumber(),
            'address' => fake()->streetAddress(),
            'city' => fake()->city(),
            'state' => fake()->state(),
            'zip' => fake()->postcode(),
            'country' => fake()->country(),
            'is_supplier' => fake()->boolean(30), // 30% chance of being a supplier
            'is_issuer' => fake()->boolean(10),   // 10% chance of being an issuer
        ];
    }

    /**
     * Indicate that the client is a supplier.
     */
    public function supplier(): static
    {
        return $this->state(fn (array $attributes) => [
            'is_supplier' => true,
        ]);
    }

    /**
     * Indicate that the client is an issuer.
     */
    public function issuer(): static
    {
        return $this->state(fn (array $attributes) => [
            'is_issuer' => true,
        ]);
    }
}
