<?php

namespace App\Http\Middleware;

use Closure;
use Illuminate\Http\Request;
use Symfony\Component\HttpFoundation\Response;

class EnsureApiUserIsActive
{
    /**
     * Impede que usuários bloqueados utilizem a API.
     *
     * @param Closure(Request): Response $next
     */
    public function handle(Request $request, Closure $next): Response
    {
        $user = $request->user();

        if ($user && ! $user->is_active) {
            return response()->json([
                'message' => 'Esta conta está bloqueada. Entre em contato com o administrador.',
                'code' => 'ACCOUNT_BLOCKED',
            ], 403);
        }

        return $next($request);
    }
}
