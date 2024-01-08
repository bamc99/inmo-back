<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Http\Requests\Admin\ResetPasswordRequest;
use App\Models\Admin;
use App\Models\AdminPasswordResetToken;
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
            // Busca el token en la base de datos
            $passwordResetToken = AdminPasswordResetToken::where('token', $token)->first();

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
            $resetPasswordToken = AdminPasswordResetToken::where('token', $resetPasswordRequest['token'])->first();

            if (!$resetPasswordToken) {
                $errors['token'] = ['El token no es válido.'];
                throw ValidationException::withMessages([
                    'token' => ['El token no es válido.']
                ]);
            }

            // Obtenemos al usuario por su correo electrónico
            $admin = Admin::where('email', $resetPasswordToken->email)->first();

            if (!$admin) {
                $errors['email'] = ['No se encontró un usuario con ese correo electrónico.'];
                throw ValidationException::withMessages([
                    'status' => 'error',
                    'email' => ['No se encontró un usuario con ese correo electrónico.']
                ]);
            }


            // if (Hash::check($resetPasswordRequest['password'], $admin->password)) {
            //     $errors['password'] = ['La nueva contraseña no puede ser igual a la contraseña anterior.'];
            //     throw ValidationException::withMessages([
            //         'status' => 'error',
            //         'password' => ['La nueva contraseña no puede ser igual a la contraseña anterior.']
            //     ]);
            // }

            // Actualizamos la contraseña del usuario
            $admin->password = Hash::make($resetPasswordRequest['password']);
            $admin->save();

            AdminPasswordResetToken::where('email', $resetPasswordToken->email)->delete();

            DB::commit();
            // Devolvemos una respuesta indicando que la contraseña ha sido actualizada
            return response()->json([
                'status' => 'success',
                'message' => 'La contraseña ha sido actualizada.'
            ], 200);
        } catch (ValidationException $e) {
            DB::rollBack();
            throw $e;
        } catch (\Throwable $th) {
            DB::rollBack();
            return response()->json([
                'status' => 'error',
                'message' => 'Ocurrió un error al actualizar la contraseña.',
                'errors' => $errors
            ], 500);
        }
    }
}
