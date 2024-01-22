<?php

namespace App\Http\Controllers\Auth;

use App\Http\Controllers\Controller;
use App\Http\Controllers\Organization\OrganizationController;
use App\Http\Requests\Auth\LoginRequest;
use App\Http\Requests\Auth\SignupOAuthRequest;
use App\Http\Requests\Auth\SignUpRequest;
use App\Http\Requests\Auth\SignUpWithInmobiliaria;
use App\Jobs\SendEmail;
use App\Mail\Email\VerifyEmail;
use App\Models\User;
use App\Models\UserEmailVerificationAttempt;
use Carbon\Carbon;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Hash;
use Illuminate\Validation\ValidationException;


class AuthController extends Controller
{

    public function login(LoginRequest $request) {

        $loginData = $request->validated();

        $user = User::where('email', $loginData['email'])
            ->with('profile')
            ->first();

        if (!$user || !Hash::check($loginData['password'], $user->password)) {
            return response(
                ['errors' =>
                    ['email' =>
                        ['El correo o la contraseña son incorrectos']
                    ]
                ], 422
            );
        }

        $tokens = $user->tokens();

        if ($tokens->count() > 0) {
            $user->tokens()->delete(); // Eliminar todos los tokens del usuario
        }

        $expiresAt = now()->addHours(8); // Crear el token con una expiración de 8 horas
        $accessToken = $user->createToken('access_token', ['*'], $expiresAt);

        return [
            'token' => [
                'accessToken' => $accessToken->plainTextToken,
                'expiresAt' => Carbon::parse($accessToken->accessToken->expires_at)->toIso8601String()
            ],
            'user' => $user
        ];

    }

    /**
     * Signup User
     *
     * @param Request $request
     * @return \Illuminate\Http\JsonResponse
     */
    public function signup(SignUpRequest $request) {
        $errors = [];
        $signupData = $request->validated();

        try {

            $ip = $request->ip();
            $userFounded = User::where('email', $signupData['email'])->first();

            if($userFounded) {
                $errors['email'] = ['El correo electrónico ya está en uso.'];
                return response()->json([
                    'message' => 'El correo electrónico ya está en uso.',
                    'errors' => $errors
                ], 400);
            }

            $token = rand(100000, 999999);
            $newUser = new User([
                'name' => $signupData['name'],
                'verification_token' => $token,
                'email' => $signupData['email'],
                'password' => Hash::make($signupData['password']),
            ]);

            $userName = $newUser->generateUsername($signupData['name'], $signupData['lastName']);
            $newUser->user_name = $userName;
            $newUser->save();

            $newUser->assignRole('user');

            $newUser->profile()->create([
                'last_name' => $signupData['lastName'],
                'phone_number' => $signupData['phoneNumber'],
            ]);

            $frontUrl = env('FRONTEND_URL', 'http://localhost:3000');
            $verificationUrl = "{$frontUrl}/auth/verify-email/{$token}?email={$newUser->email}";

            $verifyEmail = new VerifyEmail([
                'user'             => $newUser,
                'action_url'       => $verificationUrl,
                'verificationCode' => $token
            ]);
            SendEmail::dispatch($newUser->email,$verifyEmail);

            $expireInHours = config('auth.passwords.users.expire') / 60;
            $emailAttempt = new UserEmailVerificationAttempt();
            $emailAttempt->user_id = $newUser->id;
            $emailAttempt->token = $token;
            $emailAttempt->expires_at = Carbon::now()->addHours($expireInHours);
            $emailAttempt->ip_address = $ip;
            $emailAttempt->created_at = Carbon::now();
            $emailAttempt->save();

            $expiresAt = now()->addHours(8);
            $accessToken = $newUser->createToken('access_token', ['*'], $expiresAt);

            $user = User::find($newUser->id);

            return response()->json([
                'user'    => $user,
                'message' => 'Usuario registrado correctamente.',
                'token'   => [
                    'accessToken' => $accessToken->plainTextToken,
                    'expiresAt'   => Carbon::parse($accessToken->accessToken->expires_at)->toIso8601String()
                ],
            ], 200);
        } catch (ValidationException $e) {
            return response()->json([
                'message' => 'Error de validación',
                'errors' => $e->errors()
            ], 400);
        } catch (\Illuminate\Database\QueryException $e) {
            return response()->json([
                'message' => 'Error de base de datos',
                'errors' => $e->getMessage()
            ], 500);
        } catch (\Throwable $th) {
            return response()->json([
                'message' => 'Ocurrió un error al registrar el usuario.',
                'errors' => $errors
            ], 500);
        }
    }

    /**
     * Signup User With Inmobiliaria
     *
     * @param Request $request
     * @return \Illuminate\Http\JsonResponse
     */
    public function signUpWithInmobiliaria(SignUpWithInmobiliaria $request) {
        $errors = [];
        $signupData = $request->validated();

        try {

            $ip = $request->ip();
            $userFounded = User::where('email', $signupData['email'])->first();

            if($userFounded) {
                $errors['email'] = ['El correo electrónico ya está en uso.'];
                return response()->json([
                    'message' => 'El correo electrónico ya está en uso.',
                    'errors' => $errors
                ], 400);
            }

            $token = rand(100000, 999999);
            $newUser = new User([
                'name' => $signupData['name'],
                'verification_token' => $token,
                'email' => $signupData['email'],
                'password' => Hash::make($signupData['password']),
            ]);
 
            $userName = $newUser->generateUsername($signupData['name'], $signupData['lastName']);
            $newUser->user_name = $userName;
            $newUser->save();

            $newUser->assignRole('user');

            $newUser->profile()->create([
                'last_name' => $signupData['lastName'],
                'phone_number' => $signupData['phoneNumber'],
            ]);

            $frontUrl = env('FRONTEND_URL', 'http://localhost:3000');
            $verificationUrl = "{$frontUrl}/auth/verify-email/{$token}?email={$newUser->email}";

            $verifyEmail = new VerifyEmail([
                'user'             => $newUser,
                'action_url'       => $verificationUrl,
                'verificationCode' => $token
            ]);
            SendEmail::dispatch($newUser->email,$verifyEmail);

            $expireInHours = config('auth.passwords.users.expire') / 60;
            $emailAttempt = new UserEmailVerificationAttempt();
            $emailAttempt->user_id = $newUser->id;
            $emailAttempt->token = $token;
            $emailAttempt->expires_at = Carbon::now()->addHours($expireInHours);
            $emailAttempt->ip_address = $ip;
            $emailAttempt->created_at = Carbon::now();
            $emailAttempt->save();

            $expiresAt = now()->addHours(8);
            $accessToken = $newUser->createToken('access_token', ['*'], $expiresAt);

            $user = User::find($newUser->id);

            $inmobiliaria = new OrganizationController();
            $inmobiliaria->newInmoFromController($newUser->id, $signupData['organizationName']);

            return response()->json([
                'user'    => $user,
                'message' => 'Usuario registrado correctamente.',
                'token'   => [
                    'accessToken' => $accessToken->plainTextToken,
                    'expiresAt'   => Carbon::parse($accessToken->accessToken->expires_at)->toIso8601String()
                ],
            ], 200);
        } catch (ValidationException $e) {
            return response()->json([
                'message' => 'Error de validación',
                'errors' => $e->errors()
            ], 400);
        } catch (\Illuminate\Database\QueryException $e) {
            return response()->json([
                'message' => 'Error de base de datos',
                'errors' => $e->getMessage()
            ], 500);
        } catch (\Throwable $th) {
            return response()->json([
                'message' => 'Ocurrió un error al registrar el usuario.',
                'errors' => $errors,
                'more' => $th->getMessage()
            ], 500);
        }
    }

    /**
     * Signup Oauth User
     *
     * @param Request $request
     * @return \Illuminate\Http\JsonResponse
     */
    public function signupOAuth( SignupOAuthRequest $request ) {
        $signupOAuthData = $request->validated();
        return response()->json([]);
    }


    /**
     * Signup User
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
