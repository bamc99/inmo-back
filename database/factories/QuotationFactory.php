<?php

namespace Database\Factories;

use App\Models\Client;
use App\Models\Quotation;
use Illuminate\Database\Eloquent\Factories\Factory;

/**
 * @extends \Illuminate\Database\Eloquent\Factories\Factory<\App\Models\Quotation>
 */
class QuotationFactory extends Factory
{
    /**
     * Define the model's default state.
     *
     * @return array<string, mixed>
     */
    protected $model = Quotation::class;


    // private $creditTypes = [
    //     '0',
    //     '1',
    //     '2',
    //     '3',
    //     '4',
    //     'SUSTITUCION_MAS_REMODELACION',
    //     'SUSTITUCION_LIQUIDEZ',
    //     'SUSTITUCION_GASTOS',
    //     'SUSTITUCION_LIQUIDEZ_APOYO',
    //     'SUSTITUCION_GASTOS_APOYO',
    //     '5',
    //     '6',
    //     '7',
    //     'CONSTRUCCION_SCOTIABANK',
    //     'CONSTRUCCION_APOYO_INFONAVIT',
    //     '8',
    //     '9',
    //     'CONSTRUCCION_MAS_TERRENO_APOYO_INFONAVIT',
    //     'ADQUISICION_CUENTA_INFONAVIT',
    //     'CUENTA_INFONAVIT_COFINAVIT',
    //     'MEJORA',
    //     'MEJORA_APOYO_INFONAVIT',
    //     'MEJORA_MAS_REMODELACION',
    //     'REMODELACION',
    //     'COMPRA_VENTA_TERMINO_OBRA',
    //     'COMPRA_VENTA_TERMINO_OBRA_APOYO_INFONAVIT',
    //     'TERMINACION_DE_OBRA_Y_REMODELACION',
    //     'TU_CASA2',
    //     'RENOVACION',
    //     'COMPRA_MAS_RENOVACION'
    // ];


    private $creditTypes = [
        '0',
        '1',
        '2',
        '6',
    ];

    public function definition() {
        $valorVivienda = $this->faker->numberBetween(1000000, 5000000); // Valor de la vivienda
        $enganche = $this->faker->numberBetween(150000, 500000); // Enganche
        $montoCredito = $valorVivienda - $enganche; // Monto del crédito

         /** @var \App\Models\Client $client **/
        $client = Client::factory()->create();
        return [
            'additional_income'         => $this->faker->numberBetween(0, 10000), // Ingresos adicionales
            'additional_property_value' => $this->faker->numberBetween(50000, 500000), // Valor de la vivienda adicional
            'client_id'                 => $client->id, // Usuario que creó la cotización
            'construction_area'         => $this->faker->numberBetween(150000, 50000), // Área de construcción
            'credit_import'             => $this->faker->numberBetween(250000, 500000), // Importe del crédito
            'credit_type'               => $this->faker->randomElement($this->creditTypes), // Tipo de crédito (por ejemplo, adquisición tradicional
            'current_debt'              => $this->faker->numberBetween(25000, 250000), // Adeudo actual
            'down_payment'              => $enganche, // Enganche
            'infonavit_credit'          => $this->faker->numberBetween(150000, 250000), // Crédito Infonavit
            'land_area'                 => $this->faker->numberBetween(5000, 25000), // Área del terreno
            'loan_amount'               => $montoCredito, // Monto del crédito
            'loan_term'                 => $this->faker->randomElement([5, 10, 15, 20]), // Plazo del crédito
            'notarial_fees_percentage'  => $this->faker->numberBetween(0, 6), // Gastos notariales
            'monthly_income'            => $this->faker->numberBetween(15000, 25000), // Ingresos mensuales
            'project_value'             => $this->faker->numberBetween(500000, 1500000), // Valor del proyecto
            'property_value'            => $valorVivienda, // Valor de la vivienda
            'remodeling_budget'         => $this->faker->numberBetween(50000, 250000), // Presupuesto de remodelación
            'scheme'                    => $this->faker->randomElement(['fijos', 'crecientes']), // Esquema de pagos (fijos o crecientes
            'state'                     => $this->faker->state(), // Estado o región donde se encuentra la propiedad
            'sub_account'               => $this->faker->numberBetween(0, 10), // Sub Cuenta
        ];
    }

}
