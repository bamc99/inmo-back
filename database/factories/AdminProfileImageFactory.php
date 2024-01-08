<?php

namespace Database\Factories;

use App\Models\AdminProfile;
use App\Models\Attachment;
use Illuminate\Database\Eloquent\Factories\Factory;

/**
 * @extends \Illuminate\Database\Eloquent\Factories\Factory<\App\Models\AdminProfileImage>
 */
class AdminProfileImageFactory extends Factory
{
    /**
     * Define the model's default state.
     *
     * @return array<string, mixed>
     */
    public function definition(): array
    {
        return [
            'admin_profile_id' => AdminProfile::factory(), // FK
            'attachment_id' => Attachment::factory(), // FK
        ];
    }
}
