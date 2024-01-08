<?php

namespace App\Http\Controllers\Modules\Client\Auth;

use App\Http\Controllers\Controller;
use App\Http\Requests\Modules\Client\Auth\Password\ResetPasswordRequest;
use App\Models\Client;
use App\Models\ClientPasswordResetToken;
use Carbon\Carbon;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Facades\Log;

class ResetPasswordController extends Controller
{

    public function validateToken(Request $request, $token) {
        try {
            // Busca el token en la base de datos
            $passwordResetToken = ClientPasswordResetToken::where('token', $token)->first();

            // Si no existe el token, devuelve una respuesta 404
            if (!$passwordResetToken) {
                return response()->json(['valid' => false], 404);
            }

            // Verifica si el token ha expirado
            $expiresAt = Carbon::parse($passwordResetToken->expires_at);
            if ($expiresAt->isPast()) {
                return response()->json(['valid' => false], 404);
            }

            // Si el token existe y no ha expirado, devuelve una respuesta 200
            return response()->json(['valid' => true], 200);
        } catch (\Throwable $th) {
            Log::error($th);
            return response()->json(['valid' => false], 500);
        }
    }

    /**
     * Reset password
     *
     * @param Request $request
     * @return \Illuminate\Http\JsonResponse
     */
    public function reset(ResetPasswordRequest $request) {
        DB::beginTransaction();

        $errors = [];

        try {
            $resetPasswordRequest = $request->validated();
            $resetPasswordToken = ClientPasswordResetToken::where('token', $resetPasswordRequest['token'])->first();

            if (!$resetPasswordToken) {
                return response()->json([
                    'message' => 'El token no es válido.',
                    'errors' => [
                        'token' => [
                            'El token no es válido.'
                        ]
                    ]
                ], 422);
            }

            $client = Client::where('email', $resetPasswordToken->email)->first();
            if (!$client) {
                return response()->json([
                    'message' => 'No se encontró un usuario con ese correo electrónico.',
                    'errors' => [
                        'email' => [
                            'No se encontró un usuario con ese correo electrónico.'
                        ]
                    ]
                ], 422);
            }

            if (Hash::check($resetPasswordRequest['password'], $client->password)) {
                return response()->json([
                    'message' => 'La nueva contraseña no puede ser igual a la contraseña anterior.',
                    'errors' => [
                        'password' => [
                            'La nueva contraseña no puede ser igual a la contraseña anterior.'
                        ]
                    ]
                ], 422);
            }

            $client->password = Hash::make($resetPasswordRequest['password']);
            $client->save();

            ClientPasswordResetToken::where('email', $resetPasswordToken->email)->delete();

            DB::commit();
            return response()->json([
                'message' => 'La contraseña ha sido actualizada.'
            ], 200);
        } catch (\Throwable $th) {
            DB::rollBack();
            return response()->json([
                'message' => 'Ocurrió un error al actualizar la contraseña.',
                'errors' => $errors
            ], 500);
        }
    }
}
