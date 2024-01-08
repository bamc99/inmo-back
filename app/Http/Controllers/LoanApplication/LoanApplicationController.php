<?php

namespace App\Http\Controllers\LoanApplication;

use App\Http\Controllers\Controller;
use App\Http\Requests\LoanApplication\StoreLoanApplicationRequest;
use App\Models\Bank;
use App\Models\Client;
use App\Models\LoanApplication;
use App\Models\Quotation;
use App\Models\User;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Log;

class LoanApplicationController extends Controller
{
    /**
     *Store a newly created resource in storage.
     * @param Request $request
     * @return \Illuminate\Http\JsonResponse
    */
    public function store(StoreLoanApplicationRequest $request)
    {
        $storeLoanApplicationRequest = $request->validated();

        DB::beginTransaction();
        try {
            /** @var User $authUser */
            $authUser = Auth::user();
            $clientId = $storeLoanApplicationRequest['client_id'];
            $quotationId = $storeLoanApplicationRequest['quotation_id'];
            $bankSlug = $storeLoanApplicationRequest['bank_slug'];

            if(!$authUser){
                return response()->json([
                    'message' => 'No se encontró el usuario autenticado.',
                    'errors'  => [
                        'auth_user' => 'No se encontró el usuario autenticado.'
                    ]
                ], 400);
            }

            if(!$authUser->hasPermissionTo('collaborator_inmobiliaria')){
                return response()->json([
                    'message' => 'El usuario autenticado no tiene permisos para realizar esta acción.',
                    'errors'  => [
                        'auth_user' => 'El usuario autenticado no tiene permisos para realizar esta acción.'
                    ]
                ], 400);
            }

            $bank = Bank::where('slug', $bankSlug)->first();

            $client = Client::where('id', $clientId);

            if( !$authUser->hasRole('admin') || !$authUser->is_organization_owner ) {
                $client->where('user_id',  $authUser->id);
            }

            $client = $client->first();

            if(!$client){
                return response()->json([
                    'message' => 'El cliente no pertenece al usuario autenticado.',
                    'errors'  => [
                        'client' => 'El cliente no pertenece al usuario autenticado.'
                    ]
                ], 400);
            }

            $quotation = Quotation::where('client_id', $clientId)
                ->where('id', $quotationId)
                ->first();

            if(!$quotation){
                return response()->json([
                    'message' => 'La cotización no pertenece al cliente.',
                    'errors'  => []
                ], 400);
            }

            $simulation = $quotation->simulation( $bankSlug );

            if(!$simulation){
                return response()->json([
                    'message' => 'La cotización no tiene simulación para el banco seleccionado.',
                    'errors'  => []
                ], 400);
            }

            $loanApplication = LoanApplication::where('quotation_id', $quotationId)
                ->exists();

            if($loanApplication){
                return response()->json([
                    'message' => 'La cotización ya tiene una solicitud de préstamo.',
                    'errors'  => []
                ], 400);
            }

            LoanApplication::create([
                'user_id'            => $authUser->id,
                'quotation_id'       => $quotationId,
                'bank_id'            => $bank->id,
                'amortization_data'  => json_encode($simulation['montos'], JSON_UNESCAPED_UNICODE),
            ]);

            DB::commit();
            return response()->json(['message' => 'Solicitud de préstamo creada correctamente'], 201);
        } catch (\Throwable $th) {
            DB::rollBack();
            Log::error($th->getMessage());
            return response()->json([
                'message' => 'Error al crear la solicitud de préstamo.',
                'errors'  => []
            ], 500);
        }
    }

}
