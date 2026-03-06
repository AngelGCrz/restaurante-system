<?php

namespace App\Http\Middleware;

use Closure;
use Illuminate\Http\Request;
use Symfony\Component\HttpFoundation\Response;

/**
 * Verifica que el usuario autenticado tenga uno de los roles indicados.
 *
 * Uso en rutas (un rol):      ->middleware('role:admin')
 * Uso en rutas (multi-rol):   ->middleware('role:admin|cajero')
 */
class CheckRole
{
    public function handle(Request $request, Closure $next, string ...$roles): Response
    {
        // Soporte para sintaxis pipe: role:admin|cajero => $roles = ['admin|cajero']
        // Aplanamos en caso de que vengan como un único string con pipes.
        $allowed = collect($roles)
            ->flatMap(fn ($r) => explode('|', $r))
            ->map(fn ($r) => trim($r))
            ->filter()
            ->all();

        if (! $request->user() || ! $request->user()->hasRole($allowed)) {
            abort(403, 'No tienes permiso para acceder a esta sección.');
        }

        return $next($request);
    }
}
