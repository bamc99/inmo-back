<?php

namespace Database\Factories;

use Illuminate\Database\Eloquent\Factories\Factory;

/**
 * @extends \Illuminate\Database\Eloquent\Factories\Factory<\App\Models\Prospecto>
 */
class ProspectoFactory extends Factory
{
    /**
     * Define the model's default state.
     *
     * @return array<string, mixed>
     */
    public function definition(): array
    {
        return [
            'name'         =>  $this->faker->name(), // 'name' => 'John Doe
            'status'       =>  $this->faker->boolean(),
            'visual'       =>  $this->faker->boolean(),
            'phone_number' =>  $this->faker->phoneNumber(),
            'email'        =>  $this->faker->email(),
            'state'        =>  $this->faker->state(),
            'municipality' =>  $this->faker->city(),
        ];
    }
}
