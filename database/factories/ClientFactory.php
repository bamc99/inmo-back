<?php

namespace Database\Factories;

use App\Models\Client;
use App\Models\User;
use App\Models\UserProfile;
use Illuminate\Database\Eloquent\Factories\Factory;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Str;

/**
 * @extends \Illuminate\Database\Eloquent\Factories\Factory<\App\Models\Client>
 */
class ClientFactory extends Factory
{

    protected $model = Client::class;

    /**
     * Define the model's default state.
     *
     * @return array<string, mixed>
     */
    public function definition(): array
    {
         /** @var \App\Models\User $user **/
        return [
            'name'               => fake()->name(),
            'email'              => fake()->unique()->safeEmail(),
            'email_verified_at'  => now(),
            'verification_token' => Str::random(6),
            'password'           => Hash::make('Abc123456!'),
            'remember_token'     => Str::random(10),
        ];
    }

    // public function configure()
    // {
    //     /** @var \App\Models\User $user **/
    //     return $this->afterCreating(function (Client $client) {
    //         // Crea un usuario asociado al cliente
    //         $user = User::factory()->create();
    //         $client->user()->associate($user);

    //         // Crea un perfil asociado al usuario
    //         $profile = UserProfile::factory()->make();
    //         $user->profile()->save($profile);

    //         // Guarda el cliente para actualizar la relación user_id
    //         $client->save();
    //     });
    // }
}
