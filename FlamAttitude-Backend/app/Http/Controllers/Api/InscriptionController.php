<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Mail\VerifCodeMail;
use App\Support\ReglesMotDePasse;
use App\Support\Roles;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Facades\Mail;
use Illuminate\Support\Facades\RateLimiter;

/**
 * Pendant API de InscriptionController : inscription publique (compte Client
 * uniquement), simplifiée en 2 appels au lieu des 5 étapes web portées par la
 * session (impossible côté API sans cookie). Le compte est créé immédiatement
 * avec estVerif=0 et confirmé par le code reçu par email, plutôt que de garder
 * les données en attente ailleurs qu'en base.
 */
class InscriptionController extends Controller
{
    public function creer(Request $request)
    {
        $request->validate([
            'nom' => 'required|string|max:50',
            'prenom' => 'required|string|max:50',
            'telephone' => 'required|string|max:30',
            'adresse' => 'required|string|max:255',
            'pseudo' => 'required|string|max:50|unique:utilisateur,identifiant',
            'email' => 'required|email|unique:utilisateur,email',
            'mot_de_passe' => ['required', 'confirmed', ReglesMotDePasse::politique()],
        ], [
            'email.unique' => 'Cette adresse email est déjà utilisée.',
            'pseudo.unique' => 'Cet identifiant est déjà pris.',
        ]);

        $code = (string) random_int(100000, 999999);

        DB::table('utilisateur')->insert([
            'nom' => $request->input('nom'),
            'prenom' => $request->input('prenom'),
            'email' => $request->input('email'),
            'telephone' => $request->input('telephone'),
            'adresse' => $request->input('adresse'),
            'identifiant' => $request->input('pseudo'),
            'mdp' => Hash::make($request->input('mot_de_passe')),
            'pdp' => 'profil.png',
            'idStatut' => Roles::CLIENT,
            'estVerif' => 0,
            'codeVerif' => Hash::make($code),
        ]);

        Mail::to($request->input('email'))->send(new VerifCodeMail($code));

        return response()->json(['message' => 'code_envoye']);
    }

    public function verifierCode(Request $request)
    {
        $request->validate([
            'email' => 'required|email',
            'code' => 'required|digits:6',
        ]);

        $email = $request->input('email');
        $user = DB::table('utilisateur')->where('email', $email)->where('estVerif', 0)->first();

        if (! $user) {
            return response()->json(['message' => 'Aucune inscription en attente pour cet email.'], 422);
        }

        $cleLimiteur = 'inscription-code:'.$email;

        if (RateLimiter::tooManyAttempts($cleLimiteur, 5)) {
            DB::table('utilisateur')->where('idUtilisateur', $user->idUtilisateur)->delete();

            return response()->json(['message' => 'Trop de tentatives. Merci de recommencer l\'inscription.'], 429);
        }

        if (! Hash::check($request->input('code'), $user->codeVerif)) {
            RateLimiter::hit($cleLimiteur, 600);

            return response()->json(['message' => 'Code incorrect.'], 422);
        }

        RateLimiter::clear($cleLimiteur);

        DB::table('utilisateur')->where('idUtilisateur', $user->idUtilisateur)->update([
            'estVerif' => 1,
            'codeVerif' => null,
        ]);

        return response()->json(['message' => 'Compte créé avec succès.']);
    }

    public function renvoyerCode(Request $request)
    {
        $request->validate(['email' => 'required|email']);

        $email = $request->input('email');
        $user = DB::table('utilisateur')->where('email', $email)->where('estVerif', 0)->first();

        if (! $user) {
            return response()->json(['message' => 'Aucune inscription en attente pour cet email.'], 422);
        }

        $cleLimiteur = 'inscription-envoi-code:'.$email;

        if (RateLimiter::tooManyAttempts($cleLimiteur, 1)) {
            return response()->json(['message' => 'Merci de patienter quelques secondes avant de redemander un code.'], 429);
        }

        RateLimiter::hit($cleLimiteur, 30);

        $code = (string) random_int(100000, 999999);

        DB::table('utilisateur')->where('idUtilisateur', $user->idUtilisateur)->update([
            'codeVerif' => Hash::make($code),
        ]);

        Mail::to($email)->send(new VerifCodeMail($code));

        return response()->json(['message' => 'Un nouveau code vous a été envoyé.']);
    }
}
