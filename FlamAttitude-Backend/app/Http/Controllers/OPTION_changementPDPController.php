<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Mail;
use Illuminate\Support\Str;
use App\Mail\ReinitialisationMDP;
use Illuminate\Support\Facades\Hash;


class OPTION_changementPDPController extends Controller {


    public function affichageChangementPDP() {
        return view('OptionAccueil.change_pdp'); 
    }

    public function changementPDP(Request $requete) {
    
    
        // Definition du type de fichier 
        $requete->validate(['photo_profil' => 'required|image|mimes:jpeg,png,jpg|max:8192',]);



        
        $utilisateurID = session('id');
        $pseudo = session('pseudo');

        if ($requete->hasFile('photo_profil')) {
            $file = $requete->file('photo_profil');
            
            // Nommer le fichier (pseudo + timestamp)
            $extension = $file->getClientOriginalExtension();
            $nomFichier = $pseudo . "_" . time() . "." . $extension;


            if (session('photo-profil') && session('photo-profil') != 'profil.png') {
                $ancienChemin = public_path('images_profil/' . session('photo-profil'));
                if (file_exists($ancienChemin)) {
                    unlink($ancienChemin);
                }
            }

            // Déplacement vers le dossier public
            $file->move(public_path('images_profil'), $nomFichier);

            // Mise à jour de la Base de Données 
            DB::table('utilisateur')
                ->where('idUtilisateur', $utilisateurID)
                ->update(['pdp' => $nomFichier]);

            // Mise à jour de la Session
            session(['photo-profil' => $nomFichier]);

            return back()->with('confirmation', 'Votre photo de profil a été mise à jour !');
        }

        return back()->with('erreur', 'Une erreur est survenue lors de l\'envoi.');
    }
}
