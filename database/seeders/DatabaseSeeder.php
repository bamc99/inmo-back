<?php

namespace Database\Seeders;

// use Illuminate\Database\Console\Seeds\WithoutModelEvents;

use App\Models\Admin;
use App\Models\License;
use Database\Seeders\ProspectoSeeder;
use Illuminate\Database\Seeder;

class DatabaseSeeder extends Seeder
{
    /**
     * Seed the application's database.
     */
    public function run(): void
    {
        $this->call(LicenseSeeder::class);
        $this->call(RoleSeeder::class);
        $this->call(BankSeeder::class);
        $this->call(UserSeeder::class);
        // $this->call(ClientSeeder::class);
        // $this->call(QuotationSeeder::class);
        $this->call(AdminSeeder::class);
        // $this->call(ProspectoSeeder::class);
    }
}
