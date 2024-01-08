<?php

namespace Database\Seeders;

use App\Models\Admin;
use App\Models\AdminProfile;
use App\Models\Client;
use App\Models\Prospecto;
use Illuminate\Database\Console\Seeds\WithoutModelEvents;
use Illuminate\Database\Seeder;

class ProspectoSeeder extends Seeder
{
    /**
     * Run the database seeds.
     */
    public function run(): void
    {
        Admin::factory(10)->create()->each(function ($admin) {
            $admin->profile()->save(AdminProfile::factory()->make());

            Client::factory(10)->create()->each( function ($client) use ($admin) {
                $client->prospectos()->saveMany(Prospecto::factory(10)->make([
                    'admin_id' => $admin->id,
                    // 'client_id' => $client->id,
                ]));
            });
        });
    }
}
