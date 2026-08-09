<?php

namespace App\Http\Controllers;

use App\Http\Controllers\Concerns\CreeSessionUtilisateur;
use Illuminate\Http\Request;
use Illuminate\Support\Carbon;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Facades\RateLimiter;
use Illuminate\Support\Facades\Session;

/**
 * Reconnexion silencieuse dans l'app mobile via un jeton d'appareil de confiance,
 * déverrouillé par biométrie côté app (Face ID/empreinte) plutôt que par mot de
 * passe + code email. Le jeton est émis une fois par mois lors d'une connexion
 * complète (voir DoubleAuthController) et n'est jamais lui-même prolongé ici :
 * la fenêtre de 30 jours reste ancrée à cette dernière connexion complète.
 */
class ConnexionAppareilController extends Controller
{
    use CreeSessionUtilisateur;

    public function connecter(Request $request)
    {
        $request->validate(['jeton' => 'required|string']);

        [$selecteur, $validateur] = array_pad(explode('.', $request->input('jeton'), 2), 2, null);

        if (! $selecteur || ! $validateur) {
            return response()->json(['succes' => false], 422);
        }

        $cleLimiteur = 'connexion-appareil:'.$request->ip();

        if (RateLimiter::tooManyAttempts($cleLimiteur, 10)) {
            return response()->json(['succes' => false, 'erreur' => 'Trop de tentatives.'], 429);
        }

        RateLimiter::hit($cleLimiteur, 60);

        $appareil = DB::table('appareil_confie')->where('selecteur', $selecteur)->first();

        if (! $appareil || now()->greaterThan($appareil->expire_le) || ! Hash::check($validateur, $appareil->validateur_hache)) {
            return response()->json(['succes' => false], 401);
        }

        $user = DB::table('utilisateur as u')
            ->leftJoin('statut as s', 'u.idStatut', '=', 's.idStatut')
            ->where('u.idUtilisateur', $appareil->idUtilisateur)
            ->select('u.*', 's.libelle as grade')
            ->first();

        if (! $user) {
            return response()->json(['succes' => false], 401);
        }

        RateLimiter::clear($cleLimiteur);

        Session::regenerate();
        $this->creerSession($user);

        // Important : on ancre la fenêtre de 30 jours à la connexion complète d'origine
        // (celle qui a créé ce jeton), pas à maintenant, sinon elle ne s'arrêterait jamais.
        Session::put('derniere_authentification_complete', Carbon::parse($appareil->created_at)->timestamp);
        Session::put('appareil_selecteur', $selecteur);

        return response()->json(['succes' => true, 'redirection' => route('accueil')]);
    }
}
