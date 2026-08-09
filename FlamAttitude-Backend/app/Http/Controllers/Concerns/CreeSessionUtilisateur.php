<?php

namespace App\Http\Controllers\Concerns;

use App\Mail\CodeConnexionMail;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Facades\Mail;
use Illuminate\Support\Facades\RateLimiter;
use Illuminate\Support\Facades\Session;
use Illuminate\Support\Str;

trait CreeSessionUtilisateur
{
    /**
     * Place l'utilisateur en attente de code, et en envoie un nouveau par email
     * sauf si un code valide vient déjà d'être envoyé très récemment (double-clic,
     * page qui semble figée...) : dans ce cas on garde le code déjà envoyé, encore
     * valable, plutôt que d'en générer un nouveau et de spammer des emails.
     *
     * Renvoie true si un nouveau code a été envoyé, false s'il a été réutilisé.
     */
    private function envoyerCodeConnexion($user): bool
    {
        Session::regenerate();
        Session::put('idUtilisateurEnAttente', $user->idUtilisateur);

        return $this->genererEtEnvoyerCode($user);
    }

    /**
     * Génère et envoie le code de double authentification, sans toucher à la session
     * (utilisé aussi bien par la connexion web que par l'API, où il n'y a pas de session).
     */
    private function genererEtEnvoyerCode($user): bool
    {
        $cleLimiteur = 'envoi-code:'.$user->idUtilisateur;

        if (RateLimiter::tooManyAttempts($cleLimiteur, 1)) {
            return false;
        }

        RateLimiter::hit($cleLimiteur, 30);

        $code = (string) random_int(100000, 999999);

        DB::table('utilisateur')->where('idUtilisateur', $user->idUtilisateur)->update([
            'two_factor_code' => Hash::make($code),
            'two_factor_expires_at' => now()->addMinutes(10),
            'two_factor_attempts' => 0,
        ]);

        Mail::to($user->email)->send(new CodeConnexionMail($code));

        return true;
    }

    /**
     * Construit la session Laravel d'un utilisateur authentifié (mot de passe + code validés).
     */
    private function creerSession($user): void
    {
        Session::put([
            'id'           => $user->idUtilisateur,
            'connecte'     => true,
            'nom'          => $user->nom,
            'prenom'       => $user->prenom,
            'pseudo'       => $user->identifiant,
            'email'        => $user->email,
            'photo-profil' => $user->pdp,
            'grade'        => $user->grade,
            'idStatut'     => $user->idStatut,
            // Horodatage de la connexion complète (mdp + code), utilisé par CheckConnexion
            // pour exiger une reconnexion tous les 30 jours pour les clients.
            'derniere_authentification_complete' => now()->timestamp,
            'derniere_activite' => now()->timestamp,
        ]);
    }

    /**
     * Émet un jeton d'appareil de confiance (biométrie) pour cet utilisateur et le
     * renvoie sous la forme "selecteur.validateur", à stocker de façon sécurisée côté
     * client (Keychain/Keystore) pour une reconnexion silencieuse ultérieure.
     */
    private function creerJetonAppareil($user): string
    {
        $selecteur = Str::random(20);
        $validateur = Str::random(40);

        DB::table('appareil_confie')->insert([
            'idUtilisateur' => $user->idUtilisateur,
            'selecteur' => $selecteur,
            'validateur_hache' => Hash::make($validateur),
            'expire_le' => now()->addDays(30),
            'created_at' => now(),
            'updated_at' => now(),
        ]);

        return $selecteur.'.'.$validateur;
    }
}
