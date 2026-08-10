<?php

namespace App\Http\Middleware;

use App\Support\Roles;
use Closure;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Symfony\Component\HttpFoundation\Response;

/**
 * Pendant API de VerifieRole : le rôle est lu depuis l'utilisateur authentifié
 * par jeton Sanctum (`$request->user()`), pas depuis la session (inexistante côté API).
 */
class VerifieRoleApi
{
    /**
     * @param  Closure(Request): (Response)  $next
     */
    public function handle(Request $request, Closure $next, string $role): Response
    {
        $idStatut = (int) DB::table('utilisateur')->where('idUtilisateur', $request->user()->idUtilisateur)->value('idStatut');

        $autorises = match ($role) {
            'admin' => [Roles::ADMIN],
            'staff' => [Roles::ADMIN, Roles::MEMBRE_ENTREPRISE],
            'client' => [Roles::CLIENT],
            default => [],
        };

        if (! in_array($idStatut, $autorises, true)) {
            abort(403, "Vous n'avez pas accès à cette ressource.");
        }

        return $next($request);
    }
}
