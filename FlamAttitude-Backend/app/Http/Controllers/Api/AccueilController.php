<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Support\Roles;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;

/**
 * Pendant API de accueilController : mêmes statistiques par rôle, en JSON,
 * à partir de l'utilisateur authentifié par jeton Sanctum (plus de session).
 */
class AccueilController extends Controller
{
    public function index(Request $request)
    {
        $utilisateur = $request->user();
        $idStatut = (int) DB::table('utilisateur')->where('idUtilisateur', $utilisateur->idUtilisateur)->value('idStatut');

        $heure = (int) date('H');
        $salutation = ($heure >= 6 && $heure < 18) ? 'Bonjour' : 'Bonsoir';

        return response()->json([
            'salutation' => $salutation,
            'id_statut' => $idStatut,
            'stats' => match ($idStatut) {
                Roles::ADMIN => $this->statsAdmin(),
                Roles::MEMBRE_ENTREPRISE => $this->statsMembreEntreprise($utilisateur->idUtilisateur),
                Roles::CLIENT => $this->statsClient($utilisateur->idUtilisateur),
                default => [],
            },
        ]);
    }

    private function statsAdmin(): array
    {
        return [
            'clients' => DB::table('utilisateur')->where('idStatut', Roles::CLIENT)->count(),
            'membres' => DB::table('utilisateur')->where('idStatut', Roles::MEMBRE_ENTREPRISE)->count(),
            'facturesEnAttente' => DB::table('facture')->where('statut', 'en_attente')->count(),
            'rdvAVenir' => DB::table('rdv')->where('date', '>=', now()->toDateString())->count(),
        ];
    }

    private function statsMembreEntreprise(int $idMembre): array
    {
        return [
            'clients' => DB::table('utilisateur')->where('idStatut', Roles::CLIENT)->count(),
            'mesDisponibilites' => DB::table('disponibilite')->where('idMembre', $idMembre)->where('date', '>=', now()->toDateString())->count(),
            'mesRdvAVenir' => DB::table('rdv')->where('idMembre', $idMembre)->where('date', '>=', now()->toDateString())->count(),
        ];
    }

    private function statsClient(int $idClient): array
    {
        return [
            'prochainsRdv' => DB::table('rdv')->where('idClient', $idClient)->where('date', '>=', now()->toDateString())->count(),
            'facturesEnAttente' => DB::table('facture')->where('idClient', $idClient)->where('statut', 'en_attente')->count(),
            'documents' => DB::table('document')->where('idClient', $idClient)->count(),
        ];
    }
}
