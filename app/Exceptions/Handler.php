<?php

namespace App\Exceptions;

use Illuminate\Foundation\Exceptions\Handler as ExceptionHandler;
use Throwable;

class Handler extends ExceptionHandler
{
    /**
     * The list of the inputs that are never flashed to the session on validation exceptions.
     *
     * @var array<int, string>
     */
    protected $dontFlash = [
        'current_password',
        'password',
        'password_confirmation',
    ];

    /**
     * Register the exception handling callbacks for the application.
     */
    public function register(): void
    {
        $this->reportable(function (Throwable $e) {});
        $this->renderable(function (\Spatie\Permission\Exceptions\UnauthorizedException $e, $request) {
            return response()->json([
                'message' => 'No tienes permisos para realizar esta acción',
                'errors' => [
                    'permission' => 'No tienes permisos para realizar esta acción',
                ],
            ], 403);
        });
        $this->renderable(function (\Symfony\Component\HttpKernel\Exception\NotFoundHttpException $e, $request) {
            return response()->json([
                'message' => 'No se encontró el recurso solicitado',
                'errors' => [
                    'resource' => 'No se encontró el recurso solicitado',
                ],
            ], 404);
        });
        $this->renderable(function (\Symfony\Component\Routing\Exception\RouteNotFoundException $e, $request) {
            return response()->json([
                'message' => 'No se encontró el recurso solicitado',
                'errors' => [
                    'resource' => 'No se encontró el recurso solicitado',
                ],
            ], 404);
        });
        $this->renderable(function (\Symfony\Component\HttpKernel\Exception\MethodNotAllowedHttpException $e, $request) {
            return response()->json([
                'message' => 'No se encontró el recurso solicitado',
                'errors' => [
                    'resource' => 'No se encontró el recurso solicitado',
                ],
            ], 404);
        });
    }
}
