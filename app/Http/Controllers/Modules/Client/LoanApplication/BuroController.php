<?php

namespace App\Http\Controllers\Modules\Client\LoanApplication;

use App\Http\Controllers\Controller;
use App\Http\Requests\Modules\Client\LoanApplication\CheckBuroScoreAndCreateRequest;
use App\Models\Bank;
use App\Models\Client;
use App\Models\LoanApplication;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Log;

class BuroController extends Controller
{
    public function checkBuroScoreAndCreate( CheckBuroScoreAndCreateRequest $request ) {

        DB::beginTransaction();
        try {
            $checkBuroScoreRequestData = $request->validated();
            $file = $request->file('file'); // TODO: Que hay que hacer con el archivo?
            $bankSlug = $checkBuroScoreRequestData['bankSlug'];
            $quotationId = $checkBuroScoreRequestData['quotationId'];

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

            $authUser->update([
                'name'    => $checkBuroScoreRequestData['name'] ?? $authUser->name,
            ]);

            $authUser->profile()->update([
                'last_name'    => $checkBuroScoreRequestData['lastName'] ?? $authUser->last_name,
                'middle_name'  => $checkBuroScoreRequestData['middleName'] ?? $authUser->middle_name,
                'birth_date'   => $checkBuroScoreRequestData['birthDate'] ?? $authUser->birth_date,
                'country'      => $checkBuroScoreRequestData['country'] ?? $authUser->country,
                'house_number' => $checkBuroScoreRequestData['houseNumber'] ?? $authUser->house_number,
                'municipality' => $checkBuroScoreRequestData['municipality'] ?? $authUser->municipality,
                'neighborhood' => $checkBuroScoreRequestData['neighborhood'] ?? $authUser->neighborhood,
                'postal_code'  => $checkBuroScoreRequestData['postalCode'] ?? $authUser->postal_code,
                'rfc'          => $checkBuroScoreRequestData['rfc'] ?? $authUser->rfc,
                'state'        => $checkBuroScoreRequestData['state'] ?? $authUser->state,
                'street'       => $checkBuroScoreRequestData['street'] ?? $authUser->street,
            ]);

            $score = $authUser->profile->score ?? null;
            if(!$score) {
                $buroCollection = $authUser->checkBuro();
                if(isset($buroCollection['error'])){
                    return response()->json([
                        'message' => 'Ocurrió un error al procesar la solicitud, intenta de nuevo.',
                        'errors' => [
                            'buro' => 'Ocurrió un error al procesar la solicitud, intenta de nuevo.'
                        ]
                    ], 400);
                }
                $score = intval($buroCollection['response']['json']['score']['value'] ?? 0);
                $authUser->profile()->update([
                    'score' => $score,
                ]);
            }

            if ($score < 550) {
                DB::commit();
                return response()->json([
                    'message' => 'Hubo un error al procesar la solicitud. Nos pondremos en contacto contigo próximamente.',
                    'errors' => [
                        'score' => 'Hubo un error al procesar la solicitud. Nos pondremos en contacto contigo próximamente.'
                    ]
                ], 400);
            }

            $quotation = $authUser->quotations()
                ->where('id', $quotationId)
                ->first();

            if(!$quotation){
                return response()->json([
                    'message' => 'No se encontró la cotización.',
                    'errors' => [
                        'quotation' => 'No se encontró la cotización.'
                    ]
                ], 400);
            }

            if($quotation->has_application) {
                return response()->json([
                    'message' => 'Ya se ha realizado una solicitud de préstamo para esta cotización.',
                    'errors' => [
                        'quotation' => 'Ya se ha realizado una solicitud de préstamo para esta cotización.'
                    ]
                ], 400);
            }

            $bank = Bank::where('slug', $bankSlug)->first();
            if(!$bank){
                return response()->json([
                    'message' => 'No se encontró el banco solicitado.',
                    'errors' => [
                        'bank' => 'No se encontró el banco solicitado'
                    ]
                ], 400);
            }

            $simulation = $quotation->simulation( $bankSlug );
            if(!$simulation){
                return response()->json([
                    'message' => 'La cotización no tiene amortización para el banco solicitado.',
                    'errors'  => []
                ], 400);
            }

            $newLoanApplication = LoanApplication::create([
                'client_id'         => $authUser->id,
                'quotation_id'      => $quotation->id,
                'bank_id'           => $bank->id,
                'amortization_data'  => json_encode($simulation['montos'], JSON_UNESCAPED_UNICODE),
                'current_stage'     => 1,
            ]);
            $authUser->save();

            DB::commit();
            return response()->json([
                'loanApplication'=> $newLoanApplication,
            ], 200);
        } catch (\Throwable $th) {
            Log::error($th->getMessage());
            DB::rollBack();
            return response()->json([
                'message' => 'Ocurrió un error inesperado.',
                'errors' => [
                    'unexpected' => 'Ocurrió un error inesperado.'
                ],
            ], 500);
        }
    }

    public function requestScoreByAdmin(Request $request, $clientId)
    {
        DB::beginTransaction();
        try {
            $score = intval(rand(580, 900), 10); // TODO: Que hay que hacer con el archivo?

            /** @var \App\Models\Admin $authUser **/
            $authUser = Auth::user();
            if(!$authUser){
                return response()->json([
                    'message' => 'No se encontró el usuario autenticado.',
                    'errors' => [
                        'auth' => 'No se encontró el usuario autenticado.'
                    ]
                ], 400);
            }

            $client = Client::where('id', $clientId)->first();

            if(!$client){
                return response()->json([
                    'message' => 'No se encontró el cliente.',
                    'errors' => [
                        'client' => 'No se encontró el cliente.'
                    ]
                ], 400);
            }

            $client->profile()->update([
                'score' => $score,
            ]);


            DB::commit();
            sleep(3);
            return response()->json([
                'score'=> $score,
                'client' => $client->id,
                'status' => 'success'
            ], 200);
        } catch (\Throwable $th) {
            Log::error($th->getMessage());
            DB::rollBack();
            return response()->json([
                'message' => 'Ocurrió un error inesperado.',
                'errors' => [
                    'unexpected' => 'Ocurrió un error inesperado.'
                ],
            ], 500);
        }
    }
}
