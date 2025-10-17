<?php

namespace Database\Factories;

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
        // Generate US or MX phone numbers to match the SafePhoneNumberCast in Invoice model
        $isUS = $this->faker->boolean(80); // 80% US, 20% MX
        $phone = $isUS
            ? $this->faker->regexify('\+1[2-9][0-9]{9}') // US phone format
            : $this->faker->regexify('\+52[1-9][0-9]{9}'); // MX phone format

        return [
            'name' => $this->faker->name(),
            'email' => $this->faker->email(),
            'phone' => $phone,
            'address' => $this->faker->streetAddress(),
            'city' => $this->faker->city(),
            'state' => $this->faker->randomElement(['CA', 'NY', 'TX', 'FL', 'IL', 'PA', 'OH', 'GA', 'NC', 'MI']),
            'zip' => $this->faker->postcode(),
            'country' => $this->faker->country(),
        ];
    }
}
