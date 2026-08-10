<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Concerns\CreeSessionUtilisateur;
use App\Http\Controllers\Controller;
use App\Models\Utilisateur;
use App\Support\Roles;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\RateLimiter;

/**
 * Pendant API (jeton Sanctum) du flux de connexion web (AuthController + DoubleAuthController).
 * Sans session : l'app Flutter renvoie l'email à chaque étape plutôt que de compter sur un cookie.
 */
class AuthController extends Controller
{
    use CreeSessionUtilisateur;

    // Étape 1 : email + mot de passe, déclenche l'envoi du code par email
    public function login(Request $request)
    {
        $request->validate([
            'email' => 'required|email',
            'mot-de-passe' => 'required|string',
        ]);

        $email = $request->input('email');
        $mdpSaisi = $request->input('mot-de-passe');
        $cleLimiteur = 'login:'.$request->ip().'|'.strtolower($email);

        if (RateLimiter::tooManyAttempts($cleLimiteur, 5)) {
            $secondes = RateLimiter::availableIn($cleLimiteur);

            return response()->json(['message' => "Trop de tentatives. Réessayez dans {$secondes} secondes."], 429);
        }

        $user = $this->trouverParEmail($email);

        if (! $user || ! Hash::check($mdpSaisi, $user->mdp)) {
            RateLimiter::hit($cleLimiteur, 60);
            Log::warning('Échec de connexion (API)', ['email' => $email, 'ip' => $request->ip()]);

            return response()->json(['message' => 'Adresse e-mail ou mot de passe incorrect.'], 422);
        }

        RateLimiter::clear($cleLimiteur);
        $this->genererEtEnvoyerCode($user);

        return response()->json(['etape' => 'code_requis']);
    }

    // Étape 2 : vérification du code reçu par email, émission du jeton Sanctum
    public function verifierCode(Request $request)
    {
        $request->validate([
            'email' => 'required|email',
            'code' => 'required|digits:6',
        ]);

        $user = $this->trouverParEmail($request->input('email'));

        if (! $user) {
            return response()->json(['message' => 'Session expirée, reconnectez-vous.'], 422);
        }

        $cleLimiteur = 'code-connexion:'.$user->idUtilisateur;

        if (RateLimiter::tooManyAttempts($cleLimiteur, 5)) {
            $secondes = RateLimiter::availableIn($cleLimiteur);

            return response()->json(['message' => "Trop de tentatives. Réessayez dans {$secondes} secondes."], 429);
        }

        if (! $user->two_factor_code || ! $user->two_factor_expires_at || now()->greaterThan($user->two_factor_expires_at)) {
            return response()->json(['message' => 'Ce code a expiré. Demandez-en un nouveau.'], 422);
        }

        if (! Hash::check($request->input('code'), $user->two_factor_code)) {
            RateLimiter::hit($cleLimiteur, 300);
            DB::table('utilisateur')->where('idUtilisateur', $user->idUtilisateur)->increment('two_factor_attempts');
            Log::warning('Échec de vérification du code (API)', ['idUtilisateur' => $user->idUtilisateur, 'ip' => $request->ip()]);

            return response()->json(['message' => 'Code incorrect.'], 422);
        }

        RateLimiter::clear($cleLimiteur);

        DB::table('utilisateur')->where('idUtilisateur', $user->idUtilisateur)->update([
            'two_factor_code' => null,
            'two_factor_expires_at' => null,
            'two_factor_attempts' => 0,
        ]);

        return response()->json($this->reponseConnecte($user, $request->userAgent()));
    }

    // Renvoie un nouveau code, uniquement si une demande de connexion est déjà en cours
    // pour cet email (un code actif existe) : pas besoin de repasser le mot de passe,
    // mais impossible de déclencher un envoi sur un compte sans connexion en cours.
    public function renvoyerCode(Request $request)
    {
        $request->validate(['email' => 'required|email']);

        $user = $this->trouverParEmail($request->input('email'));

        if (! $user || ! $user->two_factor_expires_at) {
            return response()->json(['message' => 'Aucune demande de connexion en cours pour cet email.'], 422);
        }

        if (! $this->genererEtEnvoyerCode($user)) {
            return response()->json(['message' => 'Merci de patienter quelques secondes avant de redemander un code.'], 429);
        }

        return response()->json(['message' => 'Un nouveau code vous a été envoyé.']);
    }

    // Reconnexion silencieuse (biométrie) via un jeton d'appareil de confiance déjà émis
    public function connecterAppareil(Request $request)
    {
        $request->validate(['jeton' => 'required|string']);

        [$selecteur, $validateur] = array_pad(explode('.', $request->input('jeton'), 2), 2, null);

        if (! $selecteur || ! $validateur) {
            return response()->json(['message' => 'Jeton invalide.'], 422);
        }

        $cleLimiteur = 'connexion-appareil:'.$request->ip();

        if (RateLimiter::tooManyAttempts($cleLimiteur, 10)) {
            return response()->json(['message' => 'Trop de tentatives.'], 429);
        }

        RateLimiter::hit($cleLimiteur, 60);

        $appareil = DB::table('appareil_confie')->where('selecteur', $selecteur)->first();

        if (! $appareil || now()->greaterThan($appareil->expire_le) || ! Hash::check($validateur, $appareil->validateur_hache)) {
            return response()->json(['message' => 'Jeton expiré ou invalide.'], 401);
        }

        $user = $this->trouverParId($appareil->idUtilisateur);

        if (! $user) {
            return response()->json(['message' => 'Jeton expiré ou invalide.'], 401);
        }

        RateLimiter::clear($cleLimiteur);

        return response()->json($this->reponseConnecte($user, $request->userAgent(), emettreNouveauJetonAppareil: false));
    }

    // Révoque le jeton Sanctum courant, et le jeton d'appareil associé s'il est fourni
    public function logout(Request $request)
    {
        $request->user()->currentAccessToken()->delete();

        if ($selecteur = $request->input('selecteur_appareil')) {
            DB::table('appareil_confie')->where('selecteur', $selecteur)->delete();
        }

        return response()->json(['message' => 'Déconnecté.']);
    }

    // Profil de l'utilisateur authentifié par jeton Sanctum
    public function moi(Request $request)
    {
        $user = $this->trouverParId($request->user()->idUtilisateur);

        return response()->json($this->utilisateurEnTableau($user));
    }

    /**
     * Construit la réponse commune à une connexion réussie (code validé ou biométrie) :
     * jeton Sanctum + infos utilisateur +, pour le staff sur mobile, un nouveau jeton
     * d'appareil de confiance pour les prochaines connexions silencieuses.
     */
    private function reponseConnecte($user, ?string $userAgent, bool $emettreNouveauJetonAppareil = true): array
    {
        $utilisateurEloquent = Utilisateur::find($user->idUtilisateur);
        $nomAppareil = $userAgent ? substr($userAgent, 0, 255) : 'appareil';
        $jeton = $utilisateurEloquent->createToken($nomAppareil)->plainTextToken;

        $reponse = [
            'jeton' => $jeton,
            'utilisateur' => $this->utilisateurEnTableau($user),
        ];

        if ($emettreNouveauJetonAppareil && in_array((int) $user->idStatut, [Roles::ADMIN, Roles::MEMBRE_ENTREPRISE], true)) {
            $reponse['jeton_appareil'] = $this->creerJetonAppareil($user);
        }

        return $reponse;
    }

    private function utilisateurEnTableau($user): array
    {
        return [
            'id' => $user->idUtilisateur,
            'nom' => $user->nom,
            'prenom' => $user->prenom,
            'email' => $user->email,
            'pseudo' => $user->identifiant,
            'photo_profil' => $user->pdp,
            'grade' => $user->grade,
            'id_statut' => (int) $user->idStatut,
        ];
    }

    private function trouverParEmail(string $email)
    {
        return DB::table('utilisateur as u')
            ->leftJoin('statut as s', 'u.idStatut', '=', 's.idStatut')
            ->where('u.email', $email)
            ->select('u.*', 's.libelle as grade')
            ->first();
    }

    private function trouverParId(int $idUtilisateur)
    {
        return DB::table('utilisateur as u')
            ->leftJoin('statut as s', 'u.idStatut', '=', 's.idStatut')
            ->where('u.idUtilisateur', $idUtilisateur)
            ->select('u.*', 's.libelle as grade')
            ->first();
    }
}
