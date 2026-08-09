<?php

namespace App\Http\Middleware;

use App\Support\Plateforme;
use App\Support\Roles;
use Closure;
use Illuminate\Http\Request;
use Symfony\Component\HttpFoundation\Response;
use Illuminate\Support\Facades\Session;

class CheckConnexion
{
    // Un client reste connecté 30 jours fixes depuis sa dernière connexion complète (mdp + code).
    private const DUREE_SESSION_CLIENT = 60 * 60 * 24 * 30;

    // Staff/admin depuis l'app mobile : comme un client, 30 jours + verrou biométrique/mdp local à chaque ouverture.
    private const DUREE_SESSION_STAFF_APP = 60 * 60 * 24 * 30;

    // Staff/admin depuis un navigateur classique : déconnexion après 5h d'inactivité réelle.
    private const DUREE_INACTIVITE_STAFF_NAVIGATEUR = 60 * 60 * 5;

    /**
     * Handle an incoming request.
     *
     * @param  Closure(Request): (Response)  $next
     */
    public function handle(Request $request, Closure $next): Response
    {
        if (! Session::has('connecte')) {
            return redirect()->route('login', ['timeout' => 1]);
        }

        $maintenant = now()->timestamp;
        $idStatut = (int) Session::get('idStatut');
        $depuisApp = Plateforme::estAppMobile($request);

        if ($idStatut === Roles::CLIENT || $depuisApp) {
            // Client (partout), ou staff/admin depuis l'app mobile : reconnexion complète tous les 30 jours.
            $derniereAuth = (int) Session::get('derniere_authentification_complete', 0);
            $duree = $idStatut === Roles::CLIENT ? self::DUREE_SESSION_CLIENT : self::DUREE_SESSION_STAFF_APP;

            if ($maintenant - $derniereAuth > $duree) {
                $request->session()->invalidate();

                return redirect()->route('login', ['timeout' => 1]);
            }
        } else {
            // Staff/admin depuis un navigateur classique : 5h d'inactivité maximum.
            $derniereActivite = (int) Session::get('derniere_activite', $maintenant);

            if ($maintenant - $derniereActivite > self::DUREE_INACTIVITE_STAFF_NAVIGATEUR) {
                $request->session()->invalidate();

                return redirect()->route('login', ['timeout' => 1]);
            }

            Session::put('derniere_activite', $maintenant);
        }

        return $next($request);
    }
}
