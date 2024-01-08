<?php

namespace Database\Seeders;

use App\Models\Bank;
use Illuminate\Database\Console\Seeds\WithoutModelEvents;
use Illuminate\Database\Seeder;
use Illuminate\Support\Str;

class BankSeeder extends Seeder
{
    /**
     * Run the database seeds.
     */
    public function run(): void
    {
        //

        Bank::create([
            'slug' => 'afirme',
            'name' => 'Afirme',
            'contact_email' => '',
            'contact_phone' => '',
        ]);

        Bank::create([
            'slug' => 'banorte',
            'name' => 'Banorte',
            'contact_email' => '',
            'contact_phone' => '',
        ]);

        Bank::create([
            'slug' => 'banregio',
            'name' => 'Banregio',
            'contact_email' => '',
            'contact_phone' => '',
        ]);

        Bank::create([
            'slug' => 'citi',
            'name' => 'Citi Banamex',
            'contact_email' => '',
            'contact_phone' => '',
        ]);

        Bank::create([
            'slug' => 'hsbc',
            'name' => 'HSBC',
            'contact_email' => '',
            'contact_phone' => '',
        ]);

        Bank::create([
            'slug' => 'hey',
            'name' => 'Hey Banco',
            'contact_email' => '',
            'contact_phone' => '',
        ]);

        Bank::create([
            'slug' => 'santander',
            'name' => 'Santander',
            'contact_email' => '',
            'contact_phone' => '',
        ]);

        Bank::create([
            'slug' => 'santanderFree',
            'name' => 'Santander',
            'contact_email' => '',
            'contact_phone' => '',
        ]);

        Bank::create([
            'slug' => 'scotiabank',
            'name' => 'Scotiabank',
            'contact_email' => '',
            'contact_phone' => '',
        ]);

    }
}
