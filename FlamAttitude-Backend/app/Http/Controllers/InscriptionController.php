<?php

namespace App\Http\Controllers;

use App\Mail\VerifCodeMail;
use App\Support\ReglesMotDePasse;
use App\Support\Roles;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Facades\Mail;
use Illuminate\Support\Facades\Session;

class InscriptionController extends Controller
{
    // ----------------------------------------------------------------------------------
    // Étape 1 : identité (nom, prénom, statut) ------------------------------------------
    // ----------------------------------------------------------------------------------

    public function Vu_Inscription_Etape1()
    {
        return view('inscription.inscriptionEtape1', ['donnees' => Session::get('form_inscription', [])]);
    }

    public function Traitement_Inscription_Etape1(Request $request)
    {
        $request->validate([
            'nom' => 'required|string|max:50',
            'prenom' => 'required|string|max:50',
            'telephone' => 'required|string|max:30',
            'adresse' => 'required|string|max:255',
        ]);

        Session::put('form_inscription', $request->only(['nom', 'prenom', 'telephone', 'adresse']));

        return redirect()->route('inscription.Etape2_VU');
    }

    // ----------------------------------------------------------------------------------
    // Étape 2 : identifiant et email ------------------------------------------------------
    // ----------------------------------------------------------------------------------

    public function Vu_Inscription_Etape2()
    {
        if (! Session::has('form_inscription.nom')) {
            return redirect()->route('inscription.Etape1_VU');
        }

        return view('inscription.inscriptionEtape2', ['donnees' => Session::get('form_inscription', [])]);
    }

    public function Traitement_Inscription_Etape2(Request $request)
    {
        if (! Session::has('form_inscription.nom')) {
            return redirect()->route('inscription.Etape1_VU');
        }

        $messages = [
            'email.unique' => 'Cette adresse email est déjà utilisée.',
            'pseudo.unique' => 'Cet identifiant est déjà pris.',
        ];

        $request->validate([
            'pseudo' => 'required|string|max:50|unique:utilisateur,identifiant',
            'email' => 'required|email|unique:utilisateur,email',
        ], $messages);

        Session::put('form_inscription', array_merge(
            Session::get('form_inscription', []),
            $request->only(['pseudo', 'email'])
        ));

        return redirect()->route('inscription.Etape3_VU');
    }

    // ----------------------------------------------------------------------------------
    // Étape 3 : mot de passe --------------------------------------------------------------
    // ----------------------------------------------------------------------------------

    public function Vu_Inscription_Etape3()
    {
        if (! Session::has('form_inscription.pseudo')) {
            return redirect()->route('inscription.Etape2_VU');
        }

        return view('inscription.inscriptionEtape3');
    }

    public function Traitement_Inscription_Etape3(Request $request)
    {
        if (! Session::has('form_inscription.pseudo')) {
            return redirect()->route('inscription.Etape2_VU');
        }

        $messages = [
            'mot-de-passe.confirmed' => 'Les mots de passe ne correspondent pas.',
        ];

        $request->validate([
            'mot-de-passe' => ['required', 'confirmed', ReglesMotDePasse::politique()],
        ], $messages);

        // On hache le mot de passe immédiatement : il ne transite jamais en clair dans la session.
        Session::put('form_inscription', array_merge(
            Session::get('form_inscription', []),
            ['mdp' => Hash::make($request->input('mot-de-passe'))]
        ));

        $codeVerif = random_int(100000, 999999);
        Session::put('codeVerif', $codeVerif);
        Session::put('codeVerif_tentatives', 0);

        Mail::to(Session::get('form_inscription.email'))->send(new VerifCodeMail($codeVerif));

        return redirect()->route('inscription.Etape4_VU');
    }

    // ----------------------------------------------------------------------------------
    // Étape 4 : vérification du code reçu par email ---------------------------------------
    // ----------------------------------------------------------------------------------

    public function Vu_Inscription_Etape4()
    {
        if (! Session::has('codeVerif')) {
            return redirect()->route('inscription.Etape1_VU');
        }

        return view('inscription.inscriptionEtape4', ['email' => Session::get('form_inscription.email')]);
    }

    public function Traitement_Inscription_Etape4(Request $request)
    {
        if (! Session::has('codeVerif')) {
            return redirect()->route('inscription.Etape1_VU');
        }

        $tentatives = Session::get('codeVerif_tentatives', 0);

        if ($tentatives >= 5) {
            Session::forget(['codeVerif', 'codeVerif_tentatives', 'form_inscription']);

            return redirect()->route('inscription.Etape1_VU')
                ->with('erreur', 'Trop de tentatives. Merci de recommencer l\'inscription.');
        }

        if ($request->input('code_saisi') == Session::get('codeVerif')) {
            return $this->Finalisation_Inscription();
        }

        Session::put('codeVerif_tentatives', $tentatives + 1);

        return back()->with('erreur', 'Code incorrect.');
    }

    // ----------------------------------------------------------------------------------
    // Ajout dans la BDD et finalisation de l'inscription  ------------------------------
    // ----------------------------------------------------------------------------------

    private function Finalisation_Inscription()
    {
        $data = Session::get('form_inscription');

        // L'inscription publique ne crée que des comptes Client : les comptes
        // admin/membre de l'entreprise sont créés depuis l'espace admin.
        DB::table('utilisateur')->insert([
            'nom' => $data['nom'],
            'prenom' => $data['prenom'],
            'email' => $data['email'],
            'telephone' => $data['telephone'],
            'adresse' => $data['adresse'],
            'identifiant' => $data['pseudo'],
            'mdp' => $data['mdp'],
            'pdp' => 'profil.png',
            'idStatut' => Roles::CLIENT,
            'estVerif' => 1,
        ]);

        Session::forget(['codeVerif', 'codeVerif_tentatives', 'form_inscription']);

        return redirect()->route('inscription.Etape5_VU');
    }

    // ----------------------------------------------------------------------------------
    // Étape 5 : succès --------------------------------------------------------------------
    // ----------------------------------------------------------------------------------

    public function Vu_Inscription_Etape5()
    {
        return view('inscription.inscriptionEtape5');
    }
}
