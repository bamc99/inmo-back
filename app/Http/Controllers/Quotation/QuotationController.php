<?php

namespace App\Http\Controllers\Quotation;

use App\Http\Controllers\Controller;
use App\Http\Requests\Quotation\QuotationStoreRequest;
use App\Models\Client;
use App\Models\Quotation;
use Illuminate\Database\QueryException;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\Log;
use Illuminate\Validation\ValidationException;

class QuotationController extends Controller
{

    /**
     * Crea una nueva cotización para un cliente.
     *
     * @param Request $request
     * @return JsonResponse
     */
    public function store(QuotationStoreRequest $request)
    { // TODO:: Enable authorize request when the create-quotation-with-client endpoint is ready
        $quotationStoreRequest = $request->validated();
        $errors = [];

        DB::beginTransaction();
        try {

            /** @var \App\Models\User $authUser **/
            $authUser = Auth::user();
            if (!$authUser) {
                $errors['auth'] = 'No se encontró el usuario autenticado.';
                return response()->json([
                    'message' => 'No se encontró el usuario autenticado.',
                    'errors' => $errors
                ], 400);
            }

            // Verificar que el cliente exista
            $client = Client::find($quotationStoreRequest['clientId']);

            if (!$client) {
                $errors['client'] = 'No se encontró el cliente.';
                return response()->json([
                    'message' => 'No se encontró el cliente.',
                    'errors' => $errors
                ], 400);
            }

            if ($client->user_id != $authUser->id) {
                $errors['client'] = 'El cliente no pertenece al usuario autenticado.';
                return response()->json([
                    'message' => 'El cliente no pertenece al usuario autenticado.',
                    'errors' => $errors
                ], 400);
            }

            $newQuotation = $client->quotations()->create([
                'additional_income'         => $quotationStoreRequest['additionalIncome'] ?? 0,
                'monthly_income'            => $quotationStoreRequest['monthlyIncome'] ?? 0,
                'additional_property_value' => $quotationStoreRequest['additionalPropertyValue'] ?? 0,
                'construction_area'         => $quotationStoreRequest['constructionArea'] ?? 0,
                'credit_import'             => $quotationStoreRequest['creditImport'] ?? 0,
                'credit_type'               => $quotationStoreRequest['creditType'] ?? 0,
                'current_debt'              => $quotationStoreRequest['currentDebt'] ?? 0,
                'down_payment'              => $quotationStoreRequest['downPayment'] ?? 0,
                "notarial_fees_percentage"  => $quotationStoreRequest['notarialFeesPercentage'] ?? 6,
                'infonavit_credit'          => $quotationStoreRequest['infonavitCredit'] ?? 0,
                'land_area'                 => $quotationStoreRequest['landArea'] ?? 0,
                'loan_amount'               => $quotationStoreRequest['loanAmount'] ?? 0,
                'loan_term'                 => $quotationStoreRequest['loanTerm'] ?? 0,
                'project_value'             => $quotationStoreRequest['projectValue'] ?? 0,
                'property_value'            => $quotationStoreRequest['propertyValue'] ?? 0,
                'remodeling_budget'         => $quotationStoreRequest['remodeling_budget'] ?? 0,
                'sub_account'               => $quotationStoreRequest['subAccount'] ?? 0,
                'scheme'                    => $quotationStoreRequest['scheme'] ?? "fijos", // 0 - Fijos | 1 - Crecientes
                'state'                     => $quotationStoreRequest['propertyState'] ?? null,
            ]);

            DB::commit();
            return response()->json(['quotation' => $newQuotation]);
        } catch (ValidationException $e) {
            Log::error($e->getMessage());
            DB::rollBack();
            return response()->json([
                'message' => 'Error de validación',
                'errors' => $e->errors()
            ], 400);
        } catch (QueryException $e) {
            Log::error($e->getMessage());
            DB::rollBack();
            return response()->json([
                'message' => 'Error interno del servidor',
                'errors' => []
            ], 500);
        } catch (\Throwable $th) {
            Log::error($th->getMessage());
            DB::rollBack();
            return response()->json([
                'message' => 'Ocurrió un error al crear la cotización.',
                'errors' => $errors,
                'exception' => $th->getMessage()
            ], 500);
        }
    }

    /**
     * Obtiene una cotización por ID de cliente y ID de cotización.
     *
     * @param Request $request
     * @param int  $clientId
     * @param int  $quotationId
     * @return JsonResponse
     */
    public function getQuotationByClientId(Request $request, $clientId, $quotationId)
    {

        if (!is_numeric($clientId)) {
            if (substr($clientId, 0, 1) === "S") {
                // Concatenar sitoi
                $url = env('SITOI_URL', 'http://localhost:80');
                $url = $url . "/openapi/getQuotation.php";
                $response = Http::get($url, [
                    "id" => substr($clientId, 1),
                    "quotation_id" => $quotationId,
                ]);
                if (!$response->failed()) {
                    $quotation = $response->json();
                    return response()->json(['quotation' => $quotation, 'simulations'=>$this->simulationsMinifiedHTTP($quotation)], 200);
                }else{
                    return response()->json([
                        'message' => 'Invalid id'
                    ], 400);
                }
                
            } else {
                return response()->json([
                    'message' => 'Invalid id'
                ], 400);
            }
            return response()->json([
                'message' => 'Invalid id'
            ], 400);
        }

        /** @var \App\Models\User $authUser **/
        $authUser = Auth::user();

        if (!$authUser) {
            return response()->json([
                'message' => 'No se encontró el usuario autenticado.',
                'errors'  => []
            ], 400);
        }

        /** @var \App\Models\Client $client **/
        $client = Client::where('id', $clientId);

        $authType = $authUser->getMorphClass();
        if ($authType !== 'App\Models\Admin') {
            if (!$authUser->hasRole('admin') || !$authUser->is_organization_owner) {
                $client->where('user_id',  $authUser->id);
            }
        }


        $client = $client->first();

        if (!$client) {
            return response()->json(['error' => 'El cliente no existe'], 404);
        }

        $quotation = Quotation::where('client_id', $clientId)
            ->where('id', $quotationId)
            ->first();

        if (!$quotation) {
            return response()->json(['error' => 'La cotización no existe'], 404);
        }

        $simulations = $quotation->simulationsMinified();

        return response()->json([
            'quotation' => $quotation,
            'simulations' => $simulations,
        ]);
    }

    /**
     * Obtiene la cotización con el banco seleccionado
     *
     * @param int  $clientId
     * @param int  $quotationId
     * @param int  $bankName
     * @return JsonResponse
     */
    public function getBankSimulation($clientId, $quotationId, $bankName)
    {

        if (!is_numeric($clientId)) {
            if (substr($clientId, 0, 1) === "S") {
                // Concatenar sitoi
                $url = env('SITOI_URL', 'http://localhost:80');
                $url = $url . "/openapi/getQuotation.php";
                $response = Http::get($url, [
                    "id" => substr($clientId, 1),
                    "quotation_id" => $quotationId,
                ]);
                if (!$response->failed()) {
                    $quotation = $response->json();
                    return response()->json(['quotation' => $quotation, 'simulation'=>$this->simulationMinified($bankName, $quotation)], 200);
                }else{
                    return response()->json([
                        'message' => 'Invalid id'
                    ], 400);
                }
                
            } else {
                return response()->json([
                    'message' => 'Invalid id'
                ], 400);
            }
            return response()->json([
                'message' => 'Invalid id'
            ], 400);
        }

        /** @var \App\Models\User $authUser **/
        $authUser = Auth::user();

        if (!$authUser) {
            return response()->json([
                'message' => 'No se encontró el usuario autenticado.',
                'errors'  => []
            ], 400);
        }

        /** @var \App\Models\Client $client **/
        $client = Client::where('id', $clientId);

        // if (!$authUser->is_admin || !$authUser->is_organization_owner) {
        //     $client->where('user_id',  $authUser->id);
        // }

        $client = $client->exists();

        if (!$client) {
            return response()->json([
                'message' => 'El cliente no existe',
                'errors'  => [
                    'client' => 'El cliente no existe'
                ]
            ], 404);
        }

        $quotation = Quotation::where('client_id', $clientId)
            ->where('id', $quotationId)
            ->first();

        if (!$quotation) {
            return response()->json([
                'message' => 'La cotización no existe',
                'errors'  => [
                    'quotation' => 'La cotización no existe'
                ]
            ], 404);
        }

        $simulation = $quotation->simulationMinified($bankName);

        if (!$simulation) {
            return response()->json([
                'message' => 'La simulación para el banco especificado no existe',
                'errors'  => [
                    'simulation' => 'La simulación para el banco especificado no existe'
                ]
            ], 404);
        }


        return response()->json([
            'quotation' => $quotation,
            'simulation' => $simulation
        ]);
    }

    public function getBankSimulationAmortization($clientId, $quotationId, $bankName)
    {

        if (!is_numeric($clientId)) {
            if (substr($clientId, 0, 1) === "S") {
                // Concatenar sitoi
                $url = env('SITOI_URL', 'http://localhost:80');
                $url = $url . "/openapi/getQuotation.php";
                $response = Http::get($url, [
                    "id" => substr($clientId, 1),
                    "quotation_id" => $quotationId,
                ]);
                if (!$response->failed()) {
                    $quotation = $response->json();
                    $simulacion = $this->simulation($bankName, $quotation);
                    return response()->json(['amortization' => $simulacion['montos']['amortizacion'] ?? []], 200);
                }else{
                    return response()->json([
                        'message' => 'Invalid id'
                    ], 400);
                }
                
            } else {
                return response()->json([
                    'message' => 'Invalid id'
                ], 400);
            }
            return response()->json([
                'message' => 'Invalid id'
            ], 400);
        }

        /** @var \App\Models\User $authUser **/
        $authUser = Auth::user();

        if (!$authUser) {
            return response()->json([
                'message' => 'No se encontró el usuario autenticado.',
                'errors'  => []
            ], 400);
        }

        /** @var \App\Models\Client $client **/
        $client = Client::where('id', $clientId);

        // if (!$authUser->is_admin || !$authUser->is_organization_owner) {
        //     $client->where('user_id',  $authUser->id);
        // }

        $client = $client->exists();

        if (!$client) {
            return response()->json([
                'message' => 'El cliente no existe',
                'errors'  => [
                    'client' => 'El cliente no existe'
                ]
            ], 404);
        }

        $quotation = Quotation::where('client_id', $clientId)
            ->where('id', $quotationId)
            ->first();

        if (!$quotation) {
            return response()->json([
                'message' => 'La cotización no existe',
                'errors'  => [
                    'quotation' => 'La cotización no existe'
                ]
            ], 404);
        }

        $simulation = $quotation->simulation($bankName);

        if (!$simulation) {
            return response()->json([
                'message' => 'La simulación para el banco especificado no existe',
                'errors'  => [
                    'simulation' => 'La simulación para el banco especificado no existe'
                ]
            ], 404);
        }

        return response()->json([
            'amortization' => $simulation['montos']['amortizacion'] ?? []
        ]);
    }

    public function getSimulationsHTTP($quotation){
        $url = 'http://clientedev.toi.com.mx/comparativo/cliente';

        // Generic
        $response = Http::get($url, [
            "fechaNacimiento" => '1969-12-28',
            "producto" => $quotation['tipo'],
            "plazo" => $quotation['plazo'],
            "valorVivienda" => $quotation['property_value'],
            "valorProyecto" => 0,
            "valorViviendaAdicional" => 0,
            "porcentajeNotarial" => $quotation['notariales'],
            "tipoTaza" => 2, // Media
            "sueldo" => $quotation['ingreso'],
            "subcuenta" => $quotation['subcuenta'],
            "infonavit" => $quotation['credito_infonavit'],
            "montoCredito" => $quotation['credito'],
            "estado" => $quotation['ubicacion'],
            "pagos" => $quotation['esquema'] === "fijos" ? 0 : 1, // Fijos o Crecientes
            "terreno" => $quotation['terreno'],
            "construccion" => $quotation['construccion'],
            "adeudoActual" => $quotation['adeudo_actual'],
            "importeCredito" => $quotation['importe_credito'],
            "presupuestoRemodelacion" => $quotation['presupuesto_remodelacion'],
            "enganche" => $quotation['enganche'],
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

    public function simulationsMinifiedHTTP($quotation){
        $simulations = $this->getSimulationsHTTP($quotation);
        foreach ($simulations as &$simulation) {
            unset($simulation['montos']['amortizacion']);
            unset($simulation['montos']['dataTir']);
            unset($simulation['montos']['arrayTir']);
        }
        unset($simulation);
        return $simulations;
    }

    public function simulation( $bank, $quotation) {
        $simulations = $this->getSimulationsHTTP($quotation);
        return $simulations[$bank] ?? null;
    }

    public function simulationMinified( $bank, $quotation ) {
        $simulations = $this->simulationsMinifiedHTTP($quotation);
        return $simulations[$bank] ?? null;
    }
}
