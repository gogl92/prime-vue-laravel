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
        $faker = fake();
        return [
            'name' => $faker->company(),
            'email' => $faker->unique()->companyEmail(),
            'phone' => $faker->phoneNumber(),
            'address' => $faker->streetAddress(),
            'city' => $faker->city(),
            'state' => $faker->randomElement(['CA', 'NY', 'TX', 'FL', 'IL']),
            'zip' => $faker->postcode(),
            'country' => $faker->country(),
            'is_supplier' => $faker->boolean(30), // 30% chance of being a supplier
            'is_issuer' => $faker->boolean(10),   // 10% chance of being an issuer
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
