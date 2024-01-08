<?php

namespace App\Http\Controllers\Modules\Client\Auth;

use App\Http\Controllers\Controller;
use App\Http\Requests\Modules\Client\Auth\LoginRequest;
use App\Http\Requests\Modules\Client\Auth\SignUpRequest;
use App\Jobs\SendEmail;
use App\Mail\Modules\Client\VerifyEmail;
use App\Models\Client;
use App\Models\ClientEmailVerificationAttempt;
use App\Models\Role;
use Carbon\Carbon;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Facades\Log;

class AuthController extends Controller
{
    public function login(LoginRequest $request) {

        $loginData = $request->validated();

        $client = Client::where('email', $loginData['email'])->first();

        if (!$client || !Hash::check($loginData['password'], $client->password)) {
            return response(
                ['errors' =>
                    ['email' =>
                        ['El correo o la contraseña son incorrectos']
                    ]
                ], 422
            );
        }

        $tokens = $client->tokens();

        if ($tokens->count() > 0) {
            $client->tokens()->delete(); // Eliminar todos los tokens del usuario
        }

        $expiresAt = now()->addHours(8); // Crear el token con una expiración de 8 horas
        $accessToken = $client->createToken('access_token', ['*'], $expiresAt);

        return [
            'token' => [
                'accessToken' => $accessToken->plainTextToken,
                'expiresAt' => Carbon::parse($accessToken->accessToken->expires_at)->toIso8601String()
            ],
            'user' => $client // Devolver el usuario para el sistema de cliente
        ];

    }

    public function signup(SignUpRequest $request) {
        $signupData = $request->validated();

        DB::beginTransaction();
        try {

            $ip = $request->ip();
            $userFounded = Client::where('email', $signupData['email'])->first();

            if($userFounded) {
                return response()->json([
                    'message' => 'El correo electrónico ya está en uso.',
                    'errors' => [
                        'email' => ['El correo electrónico ya está en uso.']
                    ]
                ], 400);
            }

            $token = rand(100000, 999999);
            $newClient = new Client([
                'name'               => $signupData['name'],
                'verification_token' => $token,
                'email'              => $signupData['email'],
                'password'           => Hash::make($signupData['password']),
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

            $newClient->profile()->create([
                'last_name'         => $signupData['lastName'],
                'middle_name'       => "",
                'rfc'               => null,
                'phone_number'      => $signupData['phoneNumber'] ,
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
                'token' => $token,
                'expires_at' => Carbon::now()->addHours($expireInHours),
                'ip_address' => $ip,
                'created_at' => Carbon::now()
            ]);

            $newClient->emailVerificationAttempts()->save($emailAttempt);

            $expiresAt = now()->addHours(8);
            $accessToken = $newClient->createToken('access_token', ['*'], $expiresAt);

            DB::commit();
            return response()->json([
                'user'    => $newClient,
                'message' => 'Usuario registrado correctamente.',
                'token'   => [
                    'accessToken' => $accessToken->plainTextToken,
                    'expiresAt'   => Carbon::parse($accessToken->accessToken->expires_at)->toIso8601String()
                ],
            ], 200);
        } catch (\Throwable $th) {
            DB::rollBack();
            Log::error($th->getMessage());
            return response()->json([
                'message' => 'Ocurrió un error al registrar el usuario.',
                'errors' => [
                    'client' => 'Ocurrió un error al registrar el usuario.'
                ]
            ], 500);
        }
    }

    /**
     * Logout User
     *
     * @param Request $request
     * @return \Illuminate\Http\JsonResponse
     */
    public function logout(Request $request) {
        $user = $request->user();
        $user->currentAccessToken()->delete();
        return response()->json([
            'user' => null
        ]);
    }
}
