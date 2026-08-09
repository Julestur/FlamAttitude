<?php

namespace App\Http\Middleware;

use App\Support\Roles;
use Closure;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Session;
use Symfony\Component\HttpFoundation\Response;

class VerifieRole
{
    /**
     * Vérifie que l'utilisateur connecté a le rôle requis pour accéder à la route.
     *
     * @param  Closure(Request): (Response)  $next
     */
    public function handle(Request $request, Closure $next, string $role): Response
    {
        $idStatut = (int) Session::get('idStatut');

        $autorises = match ($role) {
            'admin' => [Roles::ADMIN],
            'staff' => [Roles::ADMIN, Roles::MEMBRE_ENTREPRISE],
            'client' => [Roles::CLIENT],
            default => [],
        };

        if (! in_array($idStatut, $autorises, true)) {
            abort(403, "Vous n'avez pas accès à cette page.");
        }

        return $next($request);
    }
}
