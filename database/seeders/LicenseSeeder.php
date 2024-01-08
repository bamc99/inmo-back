<?php

namespace Database\Seeders;

use App\Models\License;
use Illuminate\Database\Console\Seeds\WithoutModelEvents;
use Illuminate\Database\Seeder;

class LicenseSeeder extends Seeder
{
    /**
     * Run the database seeds.
     */
    public function run(): void
    {
        //

        License::create([
            'name'              => 'Licencia de Organización de Desarrollo',
            'stripe_product_id' => 'prod_NwbJ2WuoEFEa4I',
            'stripe_price_id'   => 'price_1NAi7KCi3nNsoUXDkrXrA0Xq',
            'description'       => "Acceso limitado a las funcionalidades de la plataforma. Incluye Inmuebles (Comprar, Rentar, Desarrollos en preventa, Inventario, Visor Metropoli), MCRM (Clientes) y Estudios de Mercado, pero excluye Opinión de Valor y Cotizaciones y Solicitudes.",
            'price'             => ( 99 * 100 ), // en centavos, asumiendo que la moneda es MXN y el precio es 99 MXN
            'currency'          => 'mxn',
        ]);

        License::create([
            'name'              => 'Licencia de Organización de Desarrollo',
            'stripe_product_id' => 'prod_NwbKYKgOn0zdnn',
            "description"       => "Acceso completo a todas las funcionalidades de la plataforma, incluyendo Inmuebles (Comprar, Rentar, Desarrollos en preventa, Inventario, Visor Metropoli), Opinión de Valor, MCRM (Clientes, Cotizaciones, Solicitudes) y Estudios de Mercado.",
            'stripe_price_id'   => 'price_1NAi89Ci3nNsoUXDWs8l4LLY',
            'price'             => ( 199 * 100 ), // en centavos, asumiendo que la moneda es MXN y el precio es 199 MXN
            'currency'          => 'mxn',
        ]);
    }
}
