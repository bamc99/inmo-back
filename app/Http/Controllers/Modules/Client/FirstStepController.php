<?php

namespace App\Http\Controllers\Modules\Client;

use App\Http\Controllers\Controller;
use App\Http\Requests\Modules\Client\FirstStepRequest;
use App\Jobs\SendEmail;
use App\Mail\Modules\Client\VerifyEmail;
use App\Models\Client;
use App\Models\ClientEmailVerificationAttempt;
use App\Models\Role;
use Carbon\Carbon;
use Illuminate\Database\QueryException;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Facades\Log;

class FirstStepController extends Controller
{

    public function firstStep( FirstStepRequest $request ) {

        DB::beginTransaction();

        try {
            $token = rand(100000, 999999);
            $ip = $request->ip();
            $clientFirstStepRequest = $request->validated();

            $clientRequestData = $clientFirstStepRequest['client'];
            $quotationRequestData = $clientFirstStepRequest['quotation'];

            /** @var  \App\Models\Client $newClient */
            $newClient = Client::create([
                'name'               => $clientRequestData['name'],
                'email'              => $clientRequestData['email'],
                'password'           => Hash::make($clientRequestData['password']),
                'verification_token' => $token,
            ]);

            $userRole = Role::where('name', 'user')
                ->where('guard_name', 'client-api')->first();

            if(!$userRole) {
                $userRole = Role::create([
                    'name' => 'user',
                    'guard_name' => 'client-api'
                ]);
            }

            $newClient->assignRole($userRole);

            if( !$newClient ){
                return response()->json([
                    'message' => 'No se pudo crear el cliente.',
                    'errors' => [
                        'client' => 'No se pudo crear el cliente.'
                    ]
                ], 400);
            }

            $newClient->profile()->create([
                'last_name'         => "",
                'middle_name'       => "",
                'rfc'               => null,
                'phone_number'      => $clientRequestData['phoneNumber'] ,
                'birth_date'        => $clientRequestData['birthDate'],
                'monthly_income'    => $quotationRequestData['monthlyIncome'] ?? null,
                'additional_income' => $quotationRequestData['additionalIncome'] ?? null,
            ]);

            $newQuotation = $newClient->quotations()->create([
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

            $frontUrl = env('FRONTEND_URL', 'http://localhost:3000');
            $verificationUrl = "{$frontUrl}/auth/verify-email/{$token}?email={$newClient->email}";

            $verifyEmail = new VerifyEmail([
                'user'             => $newClient,
                'action_url'       => $verificationUrl,
                'verificationCode' => $token
            ]);
            SendEmail::dispatch($newClient->email,$verifyEmail);

            $expireInHours = config('auth.passwords.clients.expire') / 60;

            $emailAttempt = ClientEmailVerificationAttempt::create([
                'token'      => $token,
                'expires_at' => Carbon::now()->addHours($expireInHours),
                'ip_address' => $ip,
            ]);

            $newClient->emailVerificationAttempts()->save($emailAttempt);

            $expiresAt = now()->addHours(8);
            $accessToken = $newClient->createToken('access_token', ['*'], $expiresAt);

            DB::commit();
            return response()->json([
                'user'=> $newClient,
                'quotation' => $newQuotation,
                'token'   => [
                    'accessToken' => $accessToken->plainTextToken,
                    'expiresAt'   => Carbon::parse($accessToken->accessToken->expires_at)->toIso8601String()
                ],
            ], 200);
        } catch (QueryException $e) {
            Log::error($e->getMessage());
            DB::rollBack();
            return response()->json([
                'message' => 'Hubo un error con la base de datos',
                'errors' => [
                    'user' => 'Ocurrió un error al crear el usuario.'
                ]
            ], 500);
        } catch (\Throwable $th) {
            Log::error($th->getMessage());
            DB::rollBack();
            return response()->json([
                'message' => 'Ocurrió un error inesperado',
                'errors' => [
                    'client' => 'Ocurrió un error al crear el usuario.'
                ],
                'exception' => $th->getMessage()
            ], 500);
        }


    }
}
