<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Facades\RateLimiter;
use Illuminate\Support\Facades\Session;

/**
 * Déverrouillage local de l'app mobile (verrou biométrique/mdp affiché à chaque ouverture
 * pour le staff/admin). Ne crée pas de nouvelle session et ne réinitialise pas le délai
 * de 30 jours : c'est juste une reconfirmation "c'est toujours vous", pas une reconnexion.
 */
class VerrouAppController extends Controller
{
    public function verifierMotDePasse(Request $request)
    {
        $request->validate(['mot-de-passe' => 'required|string']);

        $idUtilisateur = Session::get('id');
        $cleLimiteur = 'verrou-app:'.$idUtilisateur;

        if (RateLimiter::tooManyAttempts($cleLimiteur, 5)) {
            $secondes = RateLimiter::availableIn($cleLimiteur);

            return response()->json([
                'succes' => false,
                'erreur' => "Trop de tentatives. Réessayez dans {$secondes} secondes.",
            ], 429);
        }

        $utilisateur = DB::table('utilisateur')->where('idUtilisateur', $idUtilisateur)->first();

        if (! $utilisateur || ! Hash::check($request->input('mot-de-passe'), $utilisateur->mdp)) {
            RateLimiter::hit($cleLimiteur, 60);

            return response()->json(['succes' => false, 'erreur' => 'Mot de passe incorrect.'], 422);
        }

        RateLimiter::clear($cleLimiteur);

        return response()->json(['succes' => true]);
    }
}
