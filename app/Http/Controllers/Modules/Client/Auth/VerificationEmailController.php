<?php

namespace App\Http\Controllers\Modules\Client\Auth;

use App\Http\Controllers\Controller;
use App\Http\Requests\Modules\Client\Auth\Email\VerifyEmailRequest;
use App\Jobs\SendEmail;
use App\Mail\Modules\Client\VerifyEmail;
use App\Models\Client;
use App\Models\ClientEmailVerificationAttempt;
use Carbon\Carbon;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Log;

class VerificationEmailController extends Controller
{
    private const MAX_EMAIL_VERIFICATION_ATTEMPTS = 5;


    public function verify(VerifyEmailRequest $request) {
        DB::beginTransaction();
        try {
            $verifyEmailRequestData = $request->validated();

            /** @var \App\Models\Client $client **/
            $client = Client::where('email', $verifyEmailRequestData['email'])->first();

            // Verificar si el usuario ya ha verificado su correo electrónico
            if ($client->is_verified) {
                return response()->json(['message' => 'El usuario ya ha verificado su correo electrónico.'], 400);
            }

            // Verificar si el token de verificación proporcionado es válido
            if ($client->verification_token !== $verifyEmailRequestData['token']) {
                return response()->json(['message' => 'El token de verificación no es válido.'], 400);
            }

            $client->markEmailAsVerified();
            $client->verification_token = null;
            $client->emailVerificationAttempts()->delete();
            $client->save();

            DB::commit();
            return response()->json(['message' => 'El correo electrónico ha sido verificado exitosamente.']);
        } catch (\Throwable $th) {
            DB::rollBack();
            return response()->json(['message' => 'Ha ocurrido un error al verificar el correo electrónico.'], 500);
        }
    }

    public function resend(Request $request) {
        DB::beginTransaction();

        try {

             /** @var \App\Models\Client $client **/
            $client = Auth::user();
            $ip = $request->ip();

            // Verificar si el usuario ya ha verificado su correo electrónico
            if ($client->is_verified) {
                return response()->json(['message' => 'El usuario ya ha verificado su correo electrónico.'], 400);
            }

            // Obtener el número de reenvío de correo electrónico de hoy
            $today = Carbon::now()->toDateString();
            $emailCount = $client
                ->emailVerificationAttempts()
                ->where('created_at', '>=', $today)
                ->count();

            if ($emailCount >= self::MAX_EMAIL_VERIFICATION_ATTEMPTS) {
                return response()->json(['message' => 'Se ha excedido el límite de reenvíos de correo electrónico para hoy.'], 400);
            }

            $token = rand(100000, 999999);
            $client->verification_token = $token;
            $client->save();

            $frontUrl = env('FRONTEND_URL', 'http://localhost:3000');
            $verificationUrl = "{$frontUrl}/auth/verify-email/{$token}?email={$client->email}";

            $verifyEmail = new VerifyEmail([
                'user' => $client,
                'action_url' => $verificationUrl,
                'verificationCode' => $token
            ]);

            SendEmail::dispatch($client->email, $verifyEmail);

            $expireInHours = config('auth.passwords.clients.expire') / 60;

            $emailAttempt = ClientEmailVerificationAttempt::create([
                'email' => $client->email,
                'token' => $token,
                'expires_at' => Carbon::now()->addHours($expireInHours),
                'ip_address' => $ip,
                'created_at' => Carbon::now()
            ]);

            $client->emailVerificationAttempts()->save($emailAttempt);

            DB::commit();
            return response()->json(['message' => 'El correo electrónico de verificación ha sido enviado exitosamente.'], 200);
        } catch (\Throwable $th) {
            Log::error($th);
            DB::rollBack();
            return response()->json(['message' => 'Ha ocurrido un error al reenviar el correo electrónico de verificación.'], 500);
        }
    }

    public function checkEmail( Request $request ) {
        $request->validate([ 'email' => 'required|email' ]);
        $email = $request->email;
        $client = Client::where('email', $email)->exists();
        return response()->json(['exists' => $client], 200);
    }
}
