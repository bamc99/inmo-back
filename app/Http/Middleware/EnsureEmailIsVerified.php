<?php

namespace App\Http\Middleware;

use Closure;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Symfony\Component\HttpFoundation\Response;

class EnsureEmailIsVerified
{
    /**
     * Handle an incoming request.
     *
     * @param  \Closure(\Illuminate\Http\Request): (\Symfony\Component\HttpFoundation\Response)  $next
     */
    public function handle(Request $request, Closure $next): Response
    {
        /** @var \App\Models\User|null $user */
        $user = Auth::user();

        if (!$user || !$user->hasVerifiedEmail()) {
            return response()->json([
                'message' => 'Debe verificar su correo electrónico.',
                'errors' => [
                    'email' => ['Debe verificar su correo electrónico.']
                ]
            ], 403);
        }

        return $next($request);
    }
}
