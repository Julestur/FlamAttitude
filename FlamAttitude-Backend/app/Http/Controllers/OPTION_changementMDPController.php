<?php

namespace App\Http\Controllers;

use App\Mail\MotDePasseModifieMail;
use App\Support\ReglesMotDePasse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Facades\Mail;


class OPTION_changementMDPController extends Controller {


     public function affichageChangement() {
        return view('OptionAccueil.change_mdp');
    }

    public function changementMDP(Request $requete) {


        // Création de messages d'erreurs en français
        $messages = [
            'ancien_MDP.required' => 'L\'ancien mot de passe est requis.',
            'MDP.required'         => 'Le nouveau mot de passe est obligatoire.',
            'MDP.confirmed'        => 'La confirmation ne correspond pas au nouveau mot de passe.',
        ];


        // Condition du formulaire
        $requete->validate([
            'ancien_MDP' => 'required',
            'MDP'         => ['required', 'confirmed', ReglesMotDePasse::politique()],
        ], $messages);




        
        // On rcupère les infos sur l'utilisateur via son ID
        $utilisateurID = session('id'); 

        if (!$utilisateurID) {
            return back()->with('erreur', 'Votre session a expiré. Veuillez vous reconnecter.');
        }

        $utilisateur = DB::table('utilisateur')->where('idUtilisateur', $utilisateurID)->first();


        // Verif que l'utilisateur existe
        if (!$utilisateur) {
            return back()->with('erreur', 'Utilisateur non trouvé.');
        }

        // Verif que l'ancien MDP soit correct
        if (!Hash::check($requete->ancien_MDP, $utilisateur->mdp)) {
            return back()->with('erreur', 'L\'ancien mot de passe est incorrect.');
        }

        // Verif que l'ancien soit différent du nouveau
        if (Hash::check($requete->MDP, $utilisateur->mdp)) {
            return back()->with('erreur', 'Le nouveau mot de passe doit être différent de l\'actuel.');
        }

        // On change la BDD
        DB::table('utilisateur')
            ->where('idUtilisateur', $utilisateurID)
            ->update([
                'mdp' => Hash::make($requete->MDP),
            ]);

        try {
            Mail::to($utilisateur->email)->send(new MotDePasseModifieMail());
        } catch (\Exception $e) {
            report($e);
        }

        return back()->with('confirmation', 'Votre mot de passe a bien été mis à jour !');
    }
}
