<?php

namespace App\Http\Controllers\Password;

use App\Http\Controllers\Controller;
use App\Http\Requests\Password\ResetPasswordRequest;
use App\Models\PasswordResetToken;
use App\Models\User;
use App\Models\UserPasswordResetToken;
use Carbon\Carbon;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Facades\Log;
use Illuminate\Validation\ValidationException;

class ResetPasswordController extends Controller
{

    public function validateToken(Request $request, $token) {
        try {
            $passwordResetToken = UserPasswordResetToken::where('token', $token)->first();
            if (!$passwordResetToken) {
                return response()->json(['valid' => false], 404);
            }

            $expiresAt = Carbon::parse($passwordResetToken->expires_at);
            if ($expiresAt->isPast()) {
                return response()->json(['valid' => false], 404);
            }

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

        try {
            $resetPasswordRequest = $request->validated();
            $resetPasswordToken = UserPasswordResetToken::where('token', $resetPasswordRequest['token'])->first();

            if (!$resetPasswordToken) {
                return response()->json([
                    'message' => 'El token no es válido.',
                    'errors' => [
                        'token' => ['El token no es válido.']
                    ]
                ], 404);
            }

            $user = User::where('email', $resetPasswordToken->email)->first();
            if (!$user) {
                return response()->json([
                    'message' => 'No se encontró un usuario con ese correo electrónico.',
                    'errors' => [
                        'email' => ['No se encontró un usuario con ese correo electrónico.']
                    ]
                ], 404);
            }

            if (Hash::check($resetPasswordRequest['password'], $user->password)) {
                return response()->json([
                    'message' => 'La nueva contraseña no puede ser igual a la contraseña anterior.',
                    'errors' => [
                        'password' => ['La nueva contraseña no puede ser igual a la contraseña anterior.']
                    ]
                ], 422);
            }

            $user->password = Hash::make($resetPasswordRequest['password']);
            $user->save();

            UserPasswordResetToken::where('email', $resetPasswordToken->email)->delete();

            DB::commit();
            return response()->json([
                'message' => 'La contraseña ha sido actualizada.'
            ], 200);
        } catch (\Throwable $th) {
            Log::info($th);
            DB::rollBack();
            return response()->json([
                'message' => 'Ocurrió un error al actualizar la contraseña.',
                'errors' => [
                    'password' => ['Ocurrió un error al actualizar la contraseña.']
                ]
            ], 500);
        }
    }
}
