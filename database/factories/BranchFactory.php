<?php

namespace Database\Factories;

use Illuminate\Database\Eloquent\Factories\Factory;

/**
 * @extends \Illuminate\Database\Eloquent\Factories\Factory<\App\Models\Branch>
 */
class BranchFactory extends Factory
{
    /**
     * Define the model's default state.
     *
     * @return array<string, mixed>
     */
    public function definition(): array
    {
        $faker = fake();
        $words = $faker->words(2, true);
        return [
            'name' => (is_string($words) ? $words : implode(' ', $words)) . ' Branch',
            'code' => strtoupper($faker->unique()->bothify('BR-###??')),
            'email' => $faker->companyEmail(),
            'phone' => $faker->phoneNumber(),
            'address' => $faker->streetAddress(),
            'city' => $faker->city(),
            'state' => $faker->randomElement(['CA', 'NY', 'TX', 'FL', 'IL']),
            'zip' => $faker->postcode(),
            'country' => $faker->country(),
            'is_active' => $faker->boolean(85), // 85% chance of being active
            'description' => $faker->optional(0.6)->sentence(),
        ];
    }

    /**
     * Indicate that the branch is active.
     */
    public function active(): static
    {
        return $this->state(fn (array $attributes) => [
            'is_active' => true,
        ]);
    }

    /**
     * Indicate that the branch is inactive.
     */
    public function inactive(): static
    {
        return $this->state(fn (array $attributes) => [
            'is_active' => false,
        ]);
    }
}
