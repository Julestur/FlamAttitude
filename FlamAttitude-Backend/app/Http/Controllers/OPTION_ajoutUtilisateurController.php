<?php
namespace App\Http\Controllers;

use App\Support\ReglesMotDePasse;
use App\Support\Roles;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Hash;

class OPTION_ajoutUtilisateurController extends Controller
{
    // ----------------------------------------------------------------------------------
    // Affichage de la première étape avec le formulaire d'inscription ------------------
    // ----------------------------------------------------------------------------------

    public function Vu_InscriptionAdmin_Etape1()
    {
        return view('OptionAccueil.ajoutUtilisateur1');
    }

    // ----------------------------------------------------------------------------------
    // Traitement du formulaire ---------------------------------------------------------
    // ----------------------------------------------------------------------------------

    public function Traitement_InscriptionAdmin_Etape1(Request $requete)
    {
        $messages = [
            'nom.required' => 'Le nom est obligatoire.',
            'prenom.required' => 'Le prénom est obligatoire.',
            'mail.required' => 'L\'adresse email est obligatoire.',
            'mail.email' => 'Le format de l\'email est invalide.',
            'mail.unique' => 'Cet email est déjà utilisé.',
            'pseudo.required' => 'L\'identifiant est obligatoire.',
            'pseudo.unique' => 'Cet identifiant est déjà pris.',
            'MDP.required' => 'Le mot de passe est obligatoire.',
            'MDP.confirmed' => 'La confirmation ne correspond pas au mot de passe.',
            'grade.required' => 'Veuillez choisir un statut.',
        ];

        $requete->validate([
            'nom' => 'required|string|max:255',
            'prenom' => 'required|string|max:255',
            'mail' => 'required|email|unique:utilisateur,email',
            'pseudo' => 'required|unique:utilisateur,identifiant',
            'MDP' => ['required', 'confirmed', ReglesMotDePasse::politique()],
            'grade' => 'required|in:Admin,Membre_Entreprise,Client',
        ], $messages);

        $statuts = [
            'Admin' => Roles::ADMIN,
            'Membre_Entreprise' => Roles::MEMBRE_ENTREPRISE,
            'Client' => Roles::CLIENT,
        ];

        DB::table('utilisateur')->insert([
            'nom' => $requete->nom,
            'prenom' => $requete->prenom,
            'email' => $requete->mail,
            'identifiant' => $requete->pseudo,
            'mdp' => Hash::make($requete['MDP']),
            'pdp' => 'profil.png',
            'idStatut' => $statuts[$requete->grade],
            'estVerif' => 1,
        ]);

        return view('OptionAccueil.ajoutUtilisateur2', ['prenom' => $requete->prenom]);
    }
}
