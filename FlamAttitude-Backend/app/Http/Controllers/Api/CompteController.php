<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Mail\MotDePasseModifieMail;
use App\Support\ReglesMotDePasse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Facades\Mail;

/**
 * Pendant API des pages "mon compte" (OPTION_changementMDPController, etc.),
 * pour l'utilisateur authentifié par jeton Sanctum.
 */
class CompteController extends Controller
{
    public function changerMotDePasse(Request $request)
    {
        $request->validate([
            'ancien_mot_de_passe' => 'required',
            'mot_de_passe' => ['required', 'confirmed', ReglesMotDePasse::politique()],
        ], [
            'ancien_mot_de_passe.required' => "L'ancien mot de passe est requis.",
            'mot_de_passe.required' => 'Le nouveau mot de passe est obligatoire.',
            'mot_de_passe.confirmed' => 'La confirmation ne correspond pas au nouveau mot de passe.',
        ]);

        $idUtilisateur = $request->user()->idUtilisateur;
        $utilisateur = DB::table('utilisateur')->where('idUtilisateur', $idUtilisateur)->first();

        if (! Hash::check($request->input('ancien_mot_de_passe'), $utilisateur->mdp)) {
            return response()->json(['message' => "L'ancien mot de passe est incorrect."], 422);
        }

        if (Hash::check($request->input('mot_de_passe'), $utilisateur->mdp)) {
            return response()->json(['message' => 'Le nouveau mot de passe doit être différent de l\'actuel.'], 422);
        }

        DB::table('utilisateur')->where('idUtilisateur', $idUtilisateur)->update([
            'mdp' => Hash::make($request->input('mot_de_passe')),
        ]);

        try {
            Mail::to($utilisateur->email)->send(new MotDePasseModifieMail());
        } catch (\Exception $e) {
            report($e);
        }

        return response()->json(['message' => 'Votre mot de passe a bien été mis à jour !']);
    }

    public function changerPhotoProfil(Request $request)
    {
        $request->validate(['photo_profil' => 'required|image|mimes:jpeg,png,jpg|max:8192']);

        $idUtilisateur = $request->user()->idUtilisateur;
        $utilisateur = DB::table('utilisateur')->where('idUtilisateur', $idUtilisateur)->first();

        $fichier = $request->file('photo_profil');
        $extension = $fichier->getClientOriginalExtension();
        $nomFichier = $utilisateur->identifiant.'_'.time().'.'.$extension;

        if ($utilisateur->pdp && $utilisateur->pdp !== 'profil.png') {
            $ancienChemin = public_path('images_profil/'.$utilisateur->pdp);
            if (file_exists($ancienChemin)) {
                unlink($ancienChemin);
            }
        }

        $fichier->move(public_path('images_profil'), $nomFichier);

        DB::table('utilisateur')->where('idUtilisateur', $idUtilisateur)->update(['pdp' => $nomFichier]);

        return response()->json([
            'message' => 'Votre photo de profil a été mise à jour !',
            'photo_profil' => $nomFichier,
        ]);
    }
}
