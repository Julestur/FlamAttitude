<?php

namespace App\Http\Controllers;

use App\Support\Roles;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;

class OPTION_suppressionUtilisateurController extends Controller
{
    // Affichage de la liste avec barre de recherche (membres de l'entreprise + clients, jamais les admins)
    public function Vu_SuppressionAdmin_Etape1(Request $request)
    {
        $search = $request->input('search');

        $query = DB::table('utilisateur as u')
            ->join('statut as s', 'u.idStatut', '=', 's.idStatut')
            ->select('u.*', 's.libelle as grade')
            ->whereIn('u.idStatut', [Roles::MEMBRE_ENTREPRISE, Roles::CLIENT]);

        if ($search) {
            $query->where(function ($q) use ($search) {
                $q->where('u.nom', 'LIKE', "%{$search}%")
                  ->orWhere('u.prenom', 'LIKE', "%{$search}%")
                  ->orWhere('u.identifiant', 'LIKE', "%{$search}%");
            });
        }

        $donnees = $query->orderBy('u.nom')->get();

        return view('OptionAccueil.suppresionUtilisateur1', compact('donnees', 'search'));
    }

    // Page de confirmation
    public function Vu_SuppressionAdmin_Etape2(Request $request)
    {
        $id = $request->query('id');
        $item = DB::table('utilisateur')->where('idUtilisateur', $id)->first();
        $nomAffichage = ($item->prenom ?? '') . ' ' . ($item->nom ?? '');

        return view('OptionAccueil.suppresionUtilisateur2', compact('item', 'id', 'nomAffichage'));
    }

    // Traitement de la suppression
    public function Traitement_SuppressionAdmin_Etape1(Request $request)
    {
        $id = $request->input('id');

        // On garde les documents/factures déjà émis, on efface juste la référence à l'auteur si c'est un membre supprimé
        DB::table('document')->where('idAjoutePar', $id)->update(['idAjoutePar' => null]);
        DB::table('facture')->where('idCreePar', $id)->update(['idCreePar' => null]);

        // Si c'est un client : ses propres documents/factures et RDV disparaissent avec lui
        DB::table('document')->where('idClient', $id)->delete();
        DB::table('facture')->where('idClient', $id)->delete();
        DB::table('rdv')->where('idClient', $id)->delete();

        // Si c'est un membre : ses RDV (proposés et réservés) disparaissent avec lui
        // (ses disponibilités et ses types proposés sont supprimés automatiquement en cascade)
        DB::table('rdv')->where('idMembre', $id)->delete();

        DB::table('utilisateur')->where('idUtilisateur', $id)->delete();

        return redirect()->route('suppressionAdmin.Etape1_VU')->with('success', 'Suppression effectuée avec succès.');
    }
}
