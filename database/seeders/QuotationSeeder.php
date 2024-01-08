<?php

namespace Database\Seeders;

use App\Models\Quotation;
use Illuminate\Database\Console\Seeds\WithoutModelEvents;
use Illuminate\Database\Seeder;

// Cotizaciones de crédito hipotecario Seeder
class QuotationSeeder extends Seeder
{
    protected $model = Quotation::class;


    /**
     * Run the database seeds.
     */
    public function run(): void
    {
        Quotation::factory(5)->create();
    }
}
