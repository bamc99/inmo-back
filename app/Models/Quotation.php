<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Casts\Attribute;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\SoftDeletes;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Str;

// Cotizaciones de crédito hipotecario
class Quotation extends Model
{
    use HasFactory, SoftDeletes;

    private $creditTypes = [
        "0"                                         => "Adquisición Tradicional",
        "1"                                         => "Apoyo Infonavit",
        "2"                                         => "Cofinavit",
        "3"                                         => "Sustitución",
        "4"                                         => "Sustitución + Apoyo Infonavit",
        "SUSTITUCION_MAS_REMODELACION"              => "Sustitución + Remodelación",
        "SUSTITUCION_LIQUIDEZ"                      => "Sustitución + Liquidez",
        "SUSTITUCION_GASTOS"                        => "Sustitución (Financiamiento de gastos",
        "SUSTITUCION_LIQUIDEZ_APOYO"                => "Sustitución + Apoyo Infonavit + Liquidez",
        "SUSTITUCION_GASTOS_APOYO"                  => "Sustitución + Apoyo Infonavit (Financiamiento de gastos",
        "5"                                         => "Liquidez",
        "6"                                         => "Terrenos",
        "7"                                         => "Construcción - Tradicional",
        "CONSTRUCCION_SCOTIABANK"                   => "Construcción",
        "CONSTRUCCION_APOYO_INFONAVIT"              => "Construcción - Apoyo Infonavit",
        "8"                                         => "Fovissste",
        "9"                                         => "Construccion mas Terreno",
        "CONSTRUCCION_MAS_TERRENO_APOYO_INFONAVIT"  => "Construcción Más Terreno - Apoyo Infonavit",
        "ADQUISICION_CUENTA_INFONAVIT"              => "Adquisición Cuenta Infonavit + Banorte",
        "CUENTA_INFONAVIT_COFINAVIT"                => "CI + CB Cofinavit",
        "MEJORA"                                    => "Mejora",
        "MEJORA_APOYO_INFONAVIT"                    => "Mejora Apoyo Infonavit",
        "MEJORA_MAS_REMODELACION"                   => "Mejora más remodelación",
        "REMODELACION"                              => "Remodelación",
        "COMPRA_VENTA_TERMINO_OBRA"                 => "Compra Venta Termino Obra - Tradicional",
        "COMPRA_VENTA_TERMINO_OBRA_APOYO_INFONAVIT" => "Compra Venta Termino Obra - Apoyo Infonavit",
        "TERMINACION_DE_OBRA_Y_REMODELACION"        => "Terminación de Obra y Remodelación",
        "TU_CASA2"                                  => "Tu casa 2",
        "RENOVACION"                                => "Renovación",
        "COMPRA_MAS_RENOVACION"                     => "Compra + Renovación"
    ];


    protected $fillable = [
        'additional_income',
        'additional_property_value',
        'construction_area',
        'credit_import',
        'credit_type',
        'current_debt',
        'down_payment',
        'infonavit_credit',
        'land_area',
        'loan_amount',
        'loan_term',
        'monthly_income',
        'notarial_fees_percentage',
        'project_value',
        'property_value',
        'remodeling_budget',
        'scheme',
        'state',
        'sub_account',
    ];

    protected $appends = [
        'credit_type_name',
        'has_application',
        'state_name'
    ];


    protected $hidden = [
        'deleted_at'
    ];

    public function stateName(): Attribute{
        $federalEntities = config('federal_entities');
        $federalEntity = collect($federalEntities)->firstWhere('slug', $this->state);
        $state = $federalEntity['state'] ?? $this->state;
        return new Attribute(
            get: fn () => Str::title($state) ?? null
        );
    }

    public function getCreditTypeNameAttribute()
    {
        return $this->creditTypes[$this->credit_type];
    }

    public function getHasApplicationAttribute()
    {
        return $this->loanApplication()->exists(); // Devuelve un booleano que indica si existe una solicitud asociada
    }

    public function loanApplication()
    {
        return $this->hasOne(LoanApplication::class);
    }

    public function client()
    {
        return $this->belongsTo(Client::class);
    }


    public function simulations(){
        $client = $this->client;

        // $cacheKey = 'simulations_' . $this->id;
        // if (Cache::has($cacheKey)) {
        //     return Cache::get($cacheKey);
        // }

        // $url = 'http://pruebacliente.toi.com.mx/comparativo/cliente';
        $url = 'http://clientedev.toi.com.mx/comparativo/cliente';

        $response = Http::get($url, [
            "fechaNacimiento" => $client->profile->birth_date ?? "",
            "producto" => $this->credit_type,
            "plazo" => $this->loan_term,
            "valorVivienda" => $this->property_value,
            "valorProyecto" => $this->project_value,
            "valorViviendaAdicional" => $this->additional_property_value,
            "porcentajeNotarial" => $this->notarial_fees_percentage,
            "tipoTaza" => 2, // Media
            "sueldo" => ($client->profile->additional_income ?? 0) + ($client->profile->monthly_income ?? 0),
            "subcuenta" => $this->sub_account,
            "infonavit" => $this->infonavit_credit,
            "montoCredito" => $this->loan_amount,
            "estado" => $this->state,
            "pagos" => $this->scheme === "fijos" ? 0 : 1, // Fijos o Crecientes
            "terreno" => $this->land_area,
            "construccion" => $this->construction_area,
            "adeudoActual" => $this->current_debt,
            "importeCredito" => $this->credit_import,
            "presupuestoRemodelacion" => $this->remodeling_budget,
            "enganche" => $this->down_payment,
        ]);
        $simulations = [
            "banorte"       => [ "montos" => null ],
            "hsbc"          => [ "montos" => null ],
            "santander"     => [ "montos" => null ],
            "scotiabank"    => [ "montos" => null ],
            "afirme"        => [ "montos" => null ],
            "citi"          => [ "montos" => null ],
            "hey"           => [ "montos" => null ],
            "santanderFree" => [ "montos" => null ],
        ];
        if (!$response->failed()) {
            $simulations = [];
            foreach ($response->json() as $key => $simulation) {
                if(isset($simulation['montos']['error'])) {
                    $simulation['montos'] = null;
                }
                $simulations[$key] = $simulation;
            }
            // Cache::put($cacheKey, $simulations, 1440); // 8
        }
        return $simulations;
    }

    public function simulationsMinified(){
        $simulations = $this->simulations();
        foreach ($simulations as &$simulation) {
            unset($simulation['montos']['amortizacion']);
            unset($simulation['montos']['dataTir']);
            unset($simulation['montos']['arrayTir']);
        }
        unset($simulation);
        return $simulations;
    }

    public function simulation( $bank ) {
        $simulations = $this->simulations();
        return $simulations[$bank] ?? null;
    }

    public function simulationMinified( $bank ) {
        $simulations = $this->simulationsMinified();
        return $simulations[$bank] ?? null;
    }
}
