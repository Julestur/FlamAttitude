<?php

namespace App\Http\Controllers;

use App\Mail\MotDePasseModifieMail;
use App\Mail\ReinitialisationMDP;
use App\Support\ReglesMotDePasse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Facades\Mail;
use Illuminate\Support\Str;

class MDP_OublieController extends Controller
{
    // Durée de validité d'un lien de réinitialisation, en minutes
    private const EXPIRATION_MINUTES = 60;

    public function sendResetLink(Request $request)
    {
        $request->validate(['email' => 'required|email']);

        $email = $request->input('email');
        $user = DB::table('utilisateur')->where('email', $email)->first();

        // Message identique que le compte existe ou non : on ne révèle jamais
        // si une adresse email est enregistrée (anti-énumération de comptes).
        $confirmation = 'Si un compte existe pour cette adresse, un lien de réinitialisation a été envoyé.';

        if (! $user) {
            return back()->with('confirmation', $confirmation);
        }

        $token = Str::random(64);
        DB::table('changement_m_d_p')->updateOrInsert(
            ['email' => $email],
            ['token' => Hash::make($token), 'created_at' => now()]
        );

        $url = route('password.reset', ['token' => $token, 'email' => $email]);

        try {
            Mail::to($email)->send(new ReinitialisationMDP($url));
        } catch (\Exception $e) {
            // On ne révèle pas l'erreur technique à l'utilisateur pour ne pas
            // laisser fuiter d'information ; elle reste dans les logs applicatifs.
            report($e);
        }

        return back()->with('confirmation', $confirmation);
    }

    public function showResetForm($token, Request $request)
    {
        $email = $request->query('email');

        return view('gestionMDP.reinitMDP_VU', [
            'token' => $token,
            'email' => $email,
        ]);
    }

    public function updatePassword(Request $request)
    {
        $messages = [
            'password.required' => 'Le nouveau mot de passe est obligatoire.',
            'password.confirmed' => 'Les deux mots de passe ne sont pas identiques.',
            'email.required' => 'L\'adresse e-mail est introuvable.',
            'email.email' => 'Le format de l\'adresse e-mail n\'est pas valide.',
        ];

        $request->validate([
            'token' => 'required',
            'email' => 'required|email',
            'password' => ['required', 'confirmed', ReglesMotDePasse::politique()],
        ], $messages);

        $resetData = DB::table('changement_m_d_p')->where('email', $request->email)->first();

        if (! $resetData || ! Hash::check($request->token, $resetData->token)) {
            return back()->with('erreur', 'Le lien est invalide ou a expiré.');
        }

        if (now()->diffInMinutes($resetData->created_at) > self::EXPIRATION_MINUTES) {
            DB::table('changement_m_d_p')->where('email', $request->email)->delete();

            return back()->with('erreur', 'Le lien a expiré. Merci de refaire une demande.');
        }

        $user = DB::table('utilisateur')->where('email', $request->email)->first();

        if (! $user) {
            return back()->with('erreur', 'Le lien est invalide ou a expiré.');
        }

        if (Hash::check($request->password, $user->mdp)) {
            return back()->with('erreur', 'Le nouveau mot de passe doit être différent de l\'ancien.');
        }

        DB::table('utilisateur')
            ->where('email', $request->email)
            ->update(['mdp' => Hash::make($request->password)]);

        DB::table('changement_m_d_p')->where('email', $request->email)->delete();

        try {
            Mail::to($request->email)->send(new MotDePasseModifieMail());
        } catch (\Exception $e) {
            report($e);
        }

        return redirect()->route('login')->with('confirmation', 'Mot de passe modifié avec succès !');
    }
}
