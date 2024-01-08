<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Http\Requests\Admin\SendResetLinkEmailRequest;
use App\Jobs\SendEmail;
use App\Mail\Password\ResetPasswordEmail;
use App\Models\Admin;
use App\Models\AdminPasswordResetToken;
use Illuminate\Http\Request;
use Carbon\Carbon;
use Illuminate\Support\Facades\DB;
use Illuminate\Validation\ValidationException;
use Illuminate\Support\Str;

class ForgotPasswordController extends Controller
{
    protected const EXPIRE_IN_HOURS = 2; // rest link expiration in hours

    /**
     * Send reset link
     *
     * @param Request $request
     * @return \Illuminate\Http\JsonResponse
     */

    public function sendResetLink(SendResetLinkEmailRequest $request) {
        DB::beginTransaction();
        $errors = [];
        try {

            $sendEmailRequest = $request->validated();

            if (!AdminPasswordResetToken::canCreatePasswordResetToken($sendEmailRequest['email'])) {
                $errors['email'] = ['Ha alcanzado el límite de tokens de restablecimiento de contraseña para hoy.'];
                throw ValidationException::withMessages($errors);
            }

            $admin = Admin::where('email', $sendEmailRequest['email'])->first();
            
            if (!$admin) {
                return response()->json([
                    'status' => 'not-found',
                    'message' => 'Oops!, al parecer esa dirección no existe.',
                ], 200);
                $errors['email'] = ['No se encontró ningún usuario con esa dirección de correo electrónico.'];
                throw ValidationException::withMessages($errors);
            }

            

            $token = Str::random(64);
            $expireInHours = config('auth.passwords.users.expire') / 60;

            AdminPasswordResetToken::create([
                'email' => $sendEmailRequest['email'],
                'token' => $token,
                'expires_at' => Carbon::now()->addHours($expireInHours),
            ]);

            $frontUrl = env('FRONTEND_URL', 'http://localhost:5173');
            $resetUrl = "{$frontUrl}/reset-password/{$token}";

            $resetPasswordEmail = new ResetPasswordEmail([
                'user' => $admin,
                'action_url' => $resetUrl,
                'code' => $token,
                'expireInHours' => $expireInHours,
            ]);
            SendEmail::dispatch($admin->email,$resetPasswordEmail);

            DB::commit();
            
            return response()->json([
                'status' => 'success',
                'message' => 'Se ha enviado un correo electrónico con un enlace de restablecimiento de contraseña a su dirección de correo electrónico.',
            ], 200);

        } catch (ValidationException $e) {
            DB::rollBack();
            throw $e;
        } catch (\Throwable $th) {
            DB::rollBack();
            return response()->json([
                'status' => 'error',
                'message' => 'No se pudo enviar el correo electrónico de restablecimiento de contraseña. Con',
                'errors' => $th->getMessage()
            ], 500);
        }
    }
}
