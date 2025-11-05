<?php

namespace Database\Factories;

use Illuminate\Database\Eloquent\Factories\Factory;

/**
 * @extends \Illuminate\Database\Eloquent\Factories\Factory<\App\Models\Customer>
 */
class CustomerFactory extends Factory
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
            'first_name' => $faker->firstName(),
            'last_name' => $faker->lastName(),
            'email' => $faker->unique()->safeEmail(),
            'phone' => $faker->phoneNumber(),
            'street_1' => $faker->streetAddress(),
            'street_2' => $faker->optional()->streetAddress(),
            'city' => $faker->city(),
            'state' => $faker->randomElement(['CA', 'NY', 'TX', 'FL', 'IL']),
            'zip' => $faker->postcode(),
            'country' => $faker->randomElement(['United States', 'Mexico']),
            'notes' => $faker->optional()->sentence(),
            'is_active' => $faker->boolean(90), // 90% chance of being active
        ];
    }

    /**
     * Indicate that the customer is inactive.
     *
     * @return static
     */
    public function inactive(): static
    {
        return $this->state(fn (array $attributes) => [
            'is_active' => false,
        ]);
    }

    /**
     * Indicate that the customer is active.
     *
     * @return static
     */
    public function active(): static
    {
        return $this->state(fn (array $attributes) => [
            'is_active' => true,
        ]);
    }
}
