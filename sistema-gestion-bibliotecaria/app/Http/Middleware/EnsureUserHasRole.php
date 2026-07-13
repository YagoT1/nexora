<?php

// Origen: Propuesta de Arquitectura v2, DA-05 "Autorización por roles". Criterio de aceptación
// Módulo 1: "Un voluntario que intenta acceder a una ruta de administración recibe un error
// de autorización, no el contenido."
//
// Registro en bootstrap/app.php (Laravel 11):
//   ->withMiddleware(function (Middleware $middleware) {
//       $middleware->alias(['role' => \App\Http\Middleware\EnsureUserHasRole::class]);
//   })
// Uso en rutas: Route::middleware('role:administrador')->group(...);
//               Route::middleware('role:administrador,personal')->group(...);

namespace App\Http\Middleware;

use Closure;
use Illuminate\Http\Request;
use Symfony\Component\HttpFoundation\Response;

class EnsureUserHasRole
{
    public function handle(Request $request, Closure $next, string ...$rolesPermitidos): Response
    {
        $usuario = $request->user();

        if (! $usuario || ! $usuario->estaActivo()) {
            abort(403, 'Usuario inactivo o no autenticado.');
        }

        if (! in_array($usuario->rol, $rolesPermitidos, true)) {
            abort(403, 'No tiene permisos para acceder a esta sección.');
        }

        return $next($request);
    }
}
