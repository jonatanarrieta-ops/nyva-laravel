<?php

namespace App\Http\Middleware;

use Closure;
use Illuminate\Http\Request;
use Symfony\Component\HttpFoundation\Response;

class EnsureEstablishmentAccess
{
    /**
     * Permite el acceso a propietarios y a usuarios con el permiso indicado.
     *
     * @param  Closure(Request): (Response)  $next
     */
    public function handle(Request $request, Closure $next, ?string $permission = null): Response
    {
        $user = $request->user();

        abort_unless($user?->isEstablishmentUser(), 403);

        if ($permission !== null) {
            abort_unless($user->accessibleEstablishments($permission)->exists(), 403);
        }

        return $next($request);
    }
}
