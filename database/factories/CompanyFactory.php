<?php

namespace Database\Factories;

use Illuminate\Database\Eloquent\Factories\Factory;

/**
 * @extends \Illuminate\Database\Eloquent\Factories\Factory<\App\Models\Company>
 */
class CompanyFactory extends Factory
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
            'street_1' => $faker->streetAddress(),
            'street_2' => $faker->optional()->streetAddress(),
            'city' => $faker->city(),
            'state' => $faker->randomElement(['CA', 'NY', 'TX', 'FL', 'IL']),
            'zip' => $faker->postcode(),
            'country' => $faker->country(),
            'phone' => $faker->phoneNumber(),
            'email' => $faker->companyEmail(),
            'tax_id' => $faker->numerify('##-#######'),
            'tax_name' => $faker->company() . ' ' . $faker->companySuffix(),
        ];
    }
}
