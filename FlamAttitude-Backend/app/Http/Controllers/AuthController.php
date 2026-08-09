<?php

namespace App\Http\Controllers;

use App\Http\Controllers\Concerns\CreeSessionUtilisateur;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\RateLimiter;

class AuthController extends Controller
{
    use CreeSessionUtilisateur;

    // Affichage de la page
    public function showLoginForm(Request $request)
    {
        // On regarde si l'URL contient ?timeout=1
        if ($request->query('timeout') == 1) {
            return view('connexion.login')->with('session_expiree', true);
        }

        return view('connexion.login');
    }

    public function logout(Request $request)
    {
        // Révoque le jeton d'appareil de confiance associé, s'il y en a un.
        $selecteur = $request->session()->get('appareil_selecteur');

        if ($selecteur) {
            DB::table('appareil_confie')->where('selecteur', $selecteur)->delete();
        }

        // On invalide complètement la session et on régénère le jeton CSRF
        $request->session()->invalidate();
        $request->session()->regenerateToken();

        // Le paramètre indique à l'app mobile d'effacer le jeton stocké localement.
        return redirect()->route('login', ['jetonAppareilRevoque' => 1]);
    }

    // Étape 1 : vérification email + mot de passe, déclenchement de la double authentification
    public function login(Request $request)
    {
        $request->validate([
            'email' => 'required|email',
            'mot-de-passe' => 'required|string',
        ]);

        $email = $request->input('email');
        $mdpSaisi = $request->input('mot-de-passe');
        $cleLimiteur = 'login:' . $request->ip() . '|' . strtolower($email);

        if (RateLimiter::tooManyAttempts($cleLimiteur, 5)) {
            $secondes = RateLimiter::availableIn($cleLimiteur);

            return back()->withErrors([
                'login_error' => "Trop de tentatives. Réessayez dans {$secondes} secondes.",
            ])->onlyInput('email');
        }

        // Recherche de l'utilisateur par email uniquement
        $user = DB::table('utilisateur as u')
            ->leftJoin('statut as s', 'u.idStatut', '=', 's.idStatut')
            ->where('u.email', $email)
            ->select('u.*', 's.libelle as grade')
            ->first();

        if (! $user || ! Hash::check($mdpSaisi, $user->mdp)) {
            RateLimiter::hit($cleLimiteur, 60);
            Log::warning('Échec de connexion', ['email' => $email, 'ip' => $request->ip()]);

            return back()->withErrors(['login_error' => 'Adresse e-mail ou mot de passe incorrect.'])->onlyInput('email');
        }

        RateLimiter::clear($cleLimiteur);

        $this->envoyerCodeConnexion($user);

        return redirect()->route('doubleAuth.afficher');
    }
}
