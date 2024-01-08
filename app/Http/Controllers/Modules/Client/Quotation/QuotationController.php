<?php

namespace App\Http\Controllers\Modules\Client\Quotation;

use App\Http\Controllers\Controller;
use App\Http\Requests\Modules\Client\Quotation\StoreQuotationRequest;
use App\Models\Quotation;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Log;

class QuotationController extends Controller
{
    public function getMyQuotations(Request $request)
    {
        $user = $request->user();
        $quotations = $user->quotations()->get();

        return response()->json([
            'quotations' => $quotations
        ]);
    }

    public function getQuotationById(Request $request, $quotationId) {
        if(!is_numeric($quotationId)){
            return response()->json([
                'message' => 'La cotización no existe',
                'errors'  => [
                    'quotation' => 'La cotización no existe'
                ]
            ], 400);
        }

        /** @var \App\Models\Client $authUser **/
        $authUser = Auth::user();
        if(!$authUser){
            return response()->json([
                'message' => 'No se encontró el usuario autenticado.',
                'errors'  => [
                    'user' => 'No se encontró el usuario autenticado.'
                ]
            ], 400);
        }

        $quotation = Quotation::where('client_id', $authUser->id)
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

        $simulations = $quotation->simulationsMinified();

        return response()->json([
            'quotation'   => $quotation,
            'simulations' => $simulations
        ]);
    }

    /**
     * Obtiene la cotización con el banco seleccionado
     *
     * @param int  $quotationId
     * @param int  $bankName
     * @return JsonResponse
    */
    public function getBankSimulation(Request $request, $quotationId, $bankName) {

        if(!is_numeric($quotationId)){
            return response()->json([
                'message' => 'Invalid id'
            ], 400);
        }

        /** @var \App\Models\Client $authUser **/
        $authUser = Auth::user();

        if(!$authUser){
            return response()->json([
                'message' => 'No se encontró el usuario autenticado.',
                'errors'  => []
            ], 400);
        }

        $quotation = Quotation::where('client_id', $authUser->id)
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

        $simulation = $quotation->simulationMinified( $bankName );

        if (!$simulation) {
            return response()->json([
                'message' => 'La simulación para el banco especificado no existe',
                'errors'  => [
                    'simulation' => 'La simulación para el banco especificado no existe'
                ]
            ], 404);
        }

        return response()->json([
            'simulation' => $simulation,
            'quotation'  => $quotation
        ]);
    }

    public function getBankSimulationAmortization(Request $request, $quotationId, $bankName) {

        if(!is_numeric($quotationId)){
            return response()->json([
                'message' => 'Invalid id'
            ], 400);
        }

        /** @var \App\Models\User $authUser **/
        $authUser = Auth::user();

        if(!$authUser){
            return response()->json([
                'message' => 'No se encontró el usuario autenticado.',
                'errors'  => [
                    'user' => 'No se encontró el usuario autenticado.'
                ]
            ], 400);
        }

        $quotation = Quotation::where('client_id', $authUser->id)
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

        $simulation = $quotation->simulation( $bankName );
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

    public function store(StoreQuotationRequest $request) {

        DB::beginTransaction();
        try {
            $quotationRequestData = $request->validated();

            /** @var \App\Models\Client $authUser **/
            $authUser = Auth::user();
            if(!$authUser){
                return response()->json([
                    'message' => 'No se encontró el usuario autenticado.',
                    'errors' => [
                        'auth' => 'No se encontró el usuario autenticado.'
                    ]
                ], 400);
            }

            $newQuotation = $authUser->quotations()->create(  [
                'additional_income'         => $quotationRequestData['additionalIncome'] ?? 0,
                'monthly_income'            => $quotationRequestData['monthlyIncome'] ?? 0,
                'additional_property_value' => $quotationRequestData['additionalPropertyValue'] ?? 0,
                'construction_area'         => $quotationRequestData['constructionArea'] ?? 0,
                'credit_import'             => $quotationRequestData['creditImport'] ?? 0,
                'credit_type'               => $quotationRequestData['creditType'] ?? 0,
                'current_debt'              => $quotationRequestData['currentDebt'] ?? 0,
                'down_payment'              => $quotationRequestData['downPayment'] ?? 0,
                'infonavit_credit'          => $quotationRequestData['infonavitCredit'] ?? 0,
                'land_area'                 => $quotationRequestData['landArea'] ?? 0,
                'loan_amount'               => $quotationRequestData['loanAmount'] ?? 0,
                'loan_term'                 => $quotationRequestData['loanTerm'] ?? 0,
                'project_value'             => $quotationRequestData['projectValue'] ?? 0,
                'notarial_fees_percentage'  => $quotationRequestData['notarialFeesPercentage'] ?? 6,
                'property_value'            => $quotationRequestData['propertyValue'] ?? 0,
                'remodeling_budget'         => $quotationRequestData['remodeling_budget'] ?? 0,
                'sub_account'               => $quotationRequestData['subAccount'] ?? 0,
                'scheme'                    => $quotationRequestData['scheme'] ?? "fijos", // 0 - Fijos | 1 - Crecientes
                'state'                     => $quotationRequestData['propertyState'] ?? null,
            ]);

            $authUser->save();

            DB::commit();
            return response()->json([
                'quotation'=> $newQuotation,
            ], 200);
        } catch (\Throwable $th) {
            Log::error($th->getMessage());
            DB::rollBack();
            return response()->json([
                'message' => 'Ocurrió un error inesperado.',
                'errors' => [
                    'quotation' => 'Ocurrió un error al guardar la cotización.'
                ],
            ], 500);
        }
    }

}
