<?php

namespace App\Http\Controllers\Modules\Client\Profile;

use App\Http\Controllers\Controller;
use App\Models\Client;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Str;
use App\Http\Requests\Modules\Client\Profile\UpdateProfileRequest;

class ProfileController extends Controller
{

    /**
     * Show the form for editing the specified resource.
     */
    public function update( UpdateProfileRequest $request) {

        DB::beginTransaction();
        try {
            $profileRequestData = $request->validated();

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

            $clientEmail = $profileRequestData['email'] ?? null;
            $existingUser = Client::where('email', $clientEmail)
                ->where('id', '<>', $authUser->id)
                ->first();

            if( $existingUser ){
                return response()->json([
                    'message' => 'No se pudo actualizar tu información.',
                    'errors' => [
                        'email' => 'El correo electrónico ya existe.'
                    ]
                ], 400);
            }

            $authUser->update([
                'name' => $profileRequestData['name'] ?? $authUser->name,
                'email' => $profileRequestData['email'] ?? $authUser->email,
            ]);

            $profileValues = [
                'last_name'         => $profileRequestData['lastName'] ?? $authUser->profile->last_name,
                'middle_name'       => $profileRequestData['middleName'] ?? $authUser->profile->middle_name,
                'rfc'               => isset($profileRequestData['rfc']) ? Str::upper($profileRequestData['rfc']) : $authUser->profile->rfc,
                'phone_number'      => $profileRequestData['phoneNumber'] ?? $authUser->profile->phone_number,
                'street'            => $profileRequestData['street'] ?? $authUser->profile->street,
                'house_number'      => $profileRequestData['houseNumber'] ?? $authUser->profile->house_number,
                'neighborhood'      => $profileRequestData['neighborhood'] ?? $authUser->profile->neighborhood,
                'municipality'      => $profileRequestData['municipality'] ?? $authUser->profile->municipality,
                'state'             => $profileRequestData['state'] ?? $authUser->profile->state,
                'postal_code'       => $profileRequestData['postalCode'] ?? $authUser->profile->postal_code,
                'country'           => $profileRequestData['country'] ?? $authUser->profile->country,
                'birth_date'        => $profileRequestData['birthDate'] ?? $authUser->profile->birth_date,
                'monthly_income'    => $profileRequestData['monthlyIncome'] ?? $authUser->profile->monthly_income,
                'additional_income' => $profileRequestData['additionalIncome'] ?? $authUser->profile->additional_income,
            ];

            Log::info($profileValues);
            $authUser->profile()->update($profileValues);

            $authUser->save();


            DB::commit();
            return response()->json([
                'user'=> $authUser,
            ], 200);
        } catch (\Throwable $th) {
            Log::error($th->getMessage());
            DB::rollBack();
            return response()->json([
                'message' => 'Ocurrió un error inesperado.',
                'errors' => [
                    'client' => 'Ocurrió un error al actualizar el cliente.'
                ],
            ], 500);
        }
    }

}
