<?php

namespace App\Http\Middleware;

use Closure;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Symfony\Component\HttpFoundation\Response;

class VerifyOrganizationStatus
{
    /**
     * Handle an incoming request.
     *
     * @param  \Closure(\Illuminate\Http\Request): (\Symfony\Component\HttpFoundation\Response)  $next
     */
    public function handle(Request $request, Closure $next): Response
    {
        // Obtiene el usuario autenticado
        /** @var \App\Models\User $user */
        $user = Auth::user();

        if( $user->is_admin ) {
            return $next($request);
        }

        // Verifica si la organización del usuario está inactiva
        if ($user && $user->my_organization && !$user->my_organization->is_active) {
            // Si la organización está inactiva, puedes redirigir al usuario, devolver un error, etc.
            return response()->json([
                'message' => 'La organización está inactiva.',
                'errors' => [
                    'organization' => 'La organización está inactiva.'
                ]
            ], 403);
        }

        return $next($request);
    }
}
