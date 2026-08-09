<?php

namespace App\Http\Controllers;

use App\Http\Controllers\Concerns\CreeSessionUtilisateur;
use App\Support\Plateforme;
use App\Support\Roles;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\RateLimiter;
use Illuminate\Support\Facades\Session;

class DoubleAuthController extends Controller
{
    use CreeSessionUtilisateur;

    // Affichage de la page de saisie du code
    public function afficherFormulaire()
    {
        $user = $this->utilisateurEnAttente();

        if (! $user) {
            return redirect()->route('login');
        }

        return view('connexion.verificationCode', ['email' => $user->email]);
    }

    // Vérification du code saisi
    public function verifier(Request $request)
    {
        $request->validate(['code' => 'required|digits:6']);

        $user = $this->utilisateurEnAttente();

        if (! $user) {
            return redirect()->route('login');
        }

        $cleLimiteur = 'code-connexion:' . $user->idUtilisateur;

        if (RateLimiter::tooManyAttempts($cleLimiteur, 5)) {
            $secondes = RateLimiter::availableIn($cleLimiteur);

            return back()->withErrors(['code' => "Trop de tentatives. Réessayez dans {$secondes} secondes."]);
        }

        if (! $user->two_factor_code || ! $user->two_factor_expires_at || now()->greaterThan($user->two_factor_expires_at)) {
            return back()->withErrors(['code' => 'Ce code a expiré. Demandez-en un nouveau.']);
        }

        if (! Hash::check($request->input('code'), $user->two_factor_code)) {
            RateLimiter::hit($cleLimiteur, 300);

            DB::table('utilisateur')->where('idUtilisateur', $user->idUtilisateur)
                ->increment('two_factor_attempts');

            Log::warning('Échec de vérification du code de connexion', ['idUtilisateur' => $user->idUtilisateur, 'ip' => $request->ip()]);

            return back()->withErrors(['code' => 'Code incorrect.']);
        }

        RateLimiter::clear($cleLimiteur);

        // Le code est à usage unique : on le purge immédiatement
        DB::table('utilisateur')->where('idUtilisateur', $user->idUtilisateur)->update([
            'two_factor_code' => null,
            'two_factor_expires_at' => null,
            'two_factor_attempts' => 0,
        ]);

        Session::forget('idUtilisateurEnAttente');
        Session::regenerate();
        $this->creerSession($user);

        // Depuis l'app mobile, le staff/admin reçoit un jeton d'appareil pour que les
        // prochaines ouvertures se fassent par biométrie sans repasser par mot de passe + email.
        if (Plateforme::estAppMobile($request) && in_array((int) $user->idStatut, [Roles::ADMIN, Roles::MEMBRE_ENTREPRISE], true)) {
            $this->genererJetonAppareil($user);
        }

        return redirect()->route('accueil');
    }

    private function genererJetonAppareil($user): void
    {
        Session::flash('nouveau_jeton_appareil', $this->creerJetonAppareil($user));
    }

    // Renvoi d'un nouveau code
    public function renvoyer(Request $request)
    {
        $user = $this->utilisateurEnAttente();

        if (! $user) {
            return redirect()->route('login');
        }

        if (! $this->envoyerCodeConnexion($user)) {
            return back()->withErrors(['code' => 'Merci de patienter quelques secondes avant de redemander un code.']);
        }

        return back()->with('confirmation', 'Un nouveau code vous a été envoyé.');
    }

    private function utilisateurEnAttente()
    {
        $idUtilisateur = Session::get('idUtilisateurEnAttente');

        if (! $idUtilisateur) {
            return null;
        }

        return DB::table('utilisateur as u')
            ->leftJoin('statut as s', 'u.idStatut', '=', 's.idStatut')
            ->where('u.idUtilisateur', $idUtilisateur)
            ->select('u.*', 's.libelle as grade')
            ->first();
    }
}
