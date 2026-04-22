<?php

namespace App\Http\Middleware;

use Closure;
use Illuminate\Http\Request;
use Symfony\Component\HttpFoundation\Response;

class IsAdmin
{
    public function handle(Request $request, Closure $next): Response
    {
        if (!$request->user() || $request->user()->type_user !== 1) {
            return response()->json([
                'message' => 'Acceso denegado. Se requiere permisos de administrador.',
            ], 403);
        }

        return $next($request);
    }
}