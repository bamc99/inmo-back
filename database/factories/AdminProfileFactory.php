<?php

namespace Database\Factories;

use Illuminate\Database\Eloquent\Factories\Factory;

/**
 * @extends \Illuminate\Database\Eloquent\Factories\Factory<\App\Models\AdminProfile>
 */
class AdminProfileFactory extends Factory
{
    /**
     * Define the model's default state.
     *
     * @return array<string, mixed>
     */
    public function definition(): array
    {
        return [
            'last_name'         => $this->faker->lastName(),
            'phone_number'      => $this->faker->e164PhoneNumber(),
            'position'          => $this->faker->jobTitle(),
            'birth_date'        => $this->faker->dateTimeBetween('-50 years', '-18 years')->format('Y-m-d'),
        ];
    }
}
