<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Mail\ReinitialisationMDP;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Facades\Mail;
use Illuminate\Support\Str;

/**
 * Pendant API de MDP_OublieController::sendResetLink. La saisie du nouveau mot
 * de passe se fait ensuite sur la page web existante (lien cliqué depuis le mail),
 * pas dans l'app : pas besoin de dupliquer cet écran côté Flutter.
 */
class MotDePasseOublieController extends Controller
{
    public function envoyer(Request $request)
    {
        $request->validate(['email' => 'required|email']);

        $email = $request->input('email');
        $user = DB::table('utilisateur')->where('email', $email)->first();

        $confirmation = 'Si un compte existe pour cette adresse, un lien de réinitialisation a été envoyé.';

        if (! $user) {
            return response()->json(['message' => $confirmation]);
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
            report($e);
        }

        return response()->json(['message' => $confirmation]);
    }
}
