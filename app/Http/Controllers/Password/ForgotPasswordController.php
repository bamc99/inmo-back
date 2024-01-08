<?php

namespace App\Http\Controllers\Password;

use App\Http\Controllers\Controller;
use App\Http\Requests\Password\SendResetLinkEmailRequest;
use App\Jobs\SendEmail;
use App\Mail\Password\ResetPasswordEmail;
use Illuminate\Http\Request;
use App\Models\User;
use App\Models\UserPasswordResetToken;
use Carbon\Carbon;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Str;
use Illuminate\Validation\ValidationException;

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

            if (!UserPasswordResetToken::canCreatePasswordResetToken($sendEmailRequest['email'])) {
                $errors['email'] = ['Ha alcanzado el límite de tokens de restablecimiento de contraseña para hoy.'];
                throw ValidationException::withMessages($errors);
            }

            $user = User::where('email', $sendEmailRequest['email'])->first();

            if (!$user) {
                $errors['email'] = ['No se encontró ningún usuario con esa dirección de correo electrónico.'];
                throw ValidationException::withMessages($errors);
            }

            $token = Str::random(64);
            $expireInHours = config('auth.passwords.users.expire') / 60;

            UserPasswordResetToken::create([
                'email' => $sendEmailRequest['email'],
                'token' => $token,
                'expires_at' => Carbon::now()->addHours($expireInHours),
            ]);

            $frontUrl = env('FRONTEND_URL', 'http://localhost:3000');
            $resetUrl = "{$frontUrl}/auth/reset-password/{$token}";

            $resetPasswordEmail = new ResetPasswordEmail([
                'user' => $user,
                'action_url' => $resetUrl,
                'code' => $token,
                'expireInHours' => $expireInHours,
            ]);
            SendEmail::dispatch($user->email,$resetPasswordEmail);

            DB::commit();
            return response()->json([
                'message' => 'Se ha enviado un correo electrónico con un enlace de restablecimiento de contraseña a su dirección de correo electrónico.'
            ], 200);

        } catch (ValidationException $e) {
            DB::rollBack();
            throw $e;
        } catch (\Throwable $th) {
            DB::rollBack();
            return response()->json([
                'message' => 'No se pudo enviar el correo electrónico de restablecimiento de contraseña.',
                'errors' => $errors
            ], 500);
        }
    }
}
