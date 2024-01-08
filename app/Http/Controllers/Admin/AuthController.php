<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Http\Requests\Admin\LoginRequest;
use App\Models\Admin;
use Carbon\Carbon;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Hash;

class AuthController extends Controller
{

    /**
     * Sign in Admin
     */
    public function login(LoginRequest $request) {

        $loginData = $request->validated();

        $admin = Admin::where('email', $loginData['email'])->first();

        if (!$admin || !Hash::check($loginData['password'], $admin->password)) {
            return response(
                ['errors' =>
                    ['email' =>
                        ['El correo o la contraseña son incorrectos']
                    ]
                ], 422
            );
        }

        $tokens = $admin->tokens();

        if ($tokens->count() > 0) {
            $admin->tokens()->delete(); // Eliminar todos los tokens del usuario
        }

        $expiresAt = now()->addHours(8); // Crear el token con una expiración de 8 horas
        $accessToken = $admin->createToken('access_token', ['*'], $expiresAt);

        return [
            'token' => [
                'accessToken' => $accessToken->plainTextToken,
                'expiresAt' => Carbon::parse($accessToken->accessToken->expires_at)->toIso8601String()
            ],
            'admin' => $admin
        ];

    }

    /**
     * Logout User
     *
     * @param Request $request
     * @return \Illuminate\Http\JsonResponse
     */
    public function logout(Request $request) {
        $admin = $request->user();
        $admin->currentAccessToken()->delete();
        return response()->json([
            'admin' => null
        ]);
    }

}
