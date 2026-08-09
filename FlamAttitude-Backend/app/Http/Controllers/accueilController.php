<?php

namespace App\Http\Controllers;

use App\Support\Roles;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Session;

class accueilController extends Controller
{
    public function index(Request $request)
    {
        if (! Session::get('connecte')) {
            return redirect('/')->withErrors(['error' => 'Veuillez vous connecter.']);
        }

        $idStatut = (int) Session::get('idStatut');
        $prenom = Session::get('prenom');

        $heure = date('H');
        $salutation = ($heure >= 6 && $heure < 18) ? 'Bonjour' : 'Bonsoir';

        return match ($idStatut) {
            Roles::ADMIN => $this->vueAdmin($salutation, $prenom),
            Roles::MEMBRE_ENTREPRISE => $this->vueMembreEntreprise($salutation, $prenom),
            Roles::CLIENT => $this->vueClient($salutation, $prenom),
            default => redirect()->route('login'),
        };
    }

    private function vueAdmin($salutation, $prenom)
    {
        $stats = [
            'clients' => DB::table('utilisateur')->where('idStatut', Roles::CLIENT)->count(),
            'membres' => DB::table('utilisateur')->where('idStatut', Roles::MEMBRE_ENTREPRISE)->count(),
            'facturesEnAttente' => DB::table('facture')->where('statut', 'en_attente')->count(),
            'rdvAVenir' => DB::table('rdv')->where('date', '>=', now()->toDateString())->count(),
        ];

        return view('accueil.admin', compact('salutation', 'prenom', 'stats'));
    }

    private function vueMembreEntreprise($salutation, $prenom)
    {
        $idMembre = Session::get('id');

        $stats = [
            'clients' => DB::table('utilisateur')->where('idStatut', Roles::CLIENT)->count(),
            'mesDisponibilites' => DB::table('disponibilite')->where('idMembre', $idMembre)->where('date', '>=', now()->toDateString())->count(),
            'mesRdvAVenir' => DB::table('rdv')->where('idMembre', $idMembre)->where('date', '>=', now()->toDateString())->count(),
        ];

        return view('accueil.membreEntreprise', compact('salutation', 'prenom', 'stats'));
    }

    private function vueClient($salutation, $prenom)
    {
        $idClient = Session::get('id');

        $stats = [
            'prochainsRdv' => DB::table('rdv')->where('idClient', $idClient)->where('date', '>=', now()->toDateString())->count(),
            'facturesEnAttente' => DB::table('facture')->where('idClient', $idClient)->where('statut', 'en_attente')->count(),
            'documents' => DB::table('document')->where('idClient', $idClient)->count(),
        ];

        return view('accueil.client', compact('salutation', 'prenom', 'stats'));
    }
}
