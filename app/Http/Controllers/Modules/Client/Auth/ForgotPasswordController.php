<?php

namespace App\Http\Controllers\Modules\Client\Auth;

use App\Http\Controllers\Controller;
use App\Http\Requests\Modules\Client\Auth\Password\SendResetPasswordLinkEmailRequest;
use App\Jobs\SendEmail;
use App\Mail\Modules\Client\Password\ResetPasswordEmail;
use App\Models\Client;
use App\Models\ClientPasswordResetToken;
use App\Models\PasswordResetToken;
use Carbon\Carbon;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
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

    public function sendResetLink(SendResetPasswordLinkEmailRequest $request) {

        DB::beginTransaction();
        try {

            $sendEmailRequest = $request->validated();

            if (!ClientPasswordResetToken::canCreatePasswordResetToken($sendEmailRequest['email'])) {
                return response()->json([
                    'message' => 'No se pudo enviar el correo electrónico de restablecimiento de contraseña.',
                    'errors' => [
                        'email' => ['Ha alcanzado el límite de tokens de restablecimiento de contraseña para hoy.']
                    ]
                ], 500);
            }

            $client = Client::where('email', $sendEmailRequest['email'])->first();

            if (!$client) {
                return response()->json([
                    'message' => 'No se pudo enviar el correo electrónico de restablecimiento de contraseña.',
                    'errors' => [
                        'email' => ['No se encontró ningún usuario con esa dirección de correo electrónico.']
                    ]
                ], 500);
            }

            $token = Str::upper(Str::random(5));
            $expireInHours = config('auth.passwords.clients.expire') / 60;

            ClientPasswordResetToken::create([
                'email' => $sendEmailRequest['email'],
                'token' => $token,
                'expires_at' => Carbon::now()->addHours($expireInHours),
            ]);

            $frontUrl = env('FRONTEND_URL', 'http://localhost:3000');
            $resetUrl = "{$frontUrl}/auth/reset-password/{$token}";

            $resetPasswordEmail = new ResetPasswordEmail([
                'user'          => $client,
                'action_url'    => $resetUrl,
                'code'          => $token,
                'expireInHours' => $expireInHours,
            ]);
            SendEmail::dispatch($client->email,$resetPasswordEmail);

            DB::commit();
            return response()->json([
                'message' => 'Se ha enviado un correo electrónico con un enlace de restablecimiento de contraseña a su dirección de correo electrónico.'
            ], 200);

        }  catch (\Throwable $th) {
            DB::rollBack();
            return response()->json([
                'message' => 'No se pudo enviar el correo electrónico de restablecimiento de contraseña.',
                'errors' => [
                    'email' => ['Ha ocurrido un error al enviar el correo electrónico de restablecimiento de contraseña.']
                ]
            ], 500);
        }
    }
}
