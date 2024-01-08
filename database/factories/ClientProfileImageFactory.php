<?php

namespace Database\Factories;

use App\Models\Attachment;
use App\Models\ClientProfile;
use Illuminate\Database\Eloquent\Factories\Factory;

/**
 * @extends \Illuminate\Database\Eloquent\Factories\Factory<\App\Models\ClientProfileImage>
 */
class ClientProfileImageFactory extends Factory
{
    /**
     * Define the model's default state.
     *
     * @return array<string, mixed>
     */
    public function definition(): array
    {
        return [
            'client_profile_id' => ClientProfile::factory(), // FK
            'attachment_id' => Attachment::factory(), // FK
        ];
    }
}
