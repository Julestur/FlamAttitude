<?php

namespace App\Http\Controllers;

use App\Support\Roles;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Session;

class StaffClientController extends Controller
{
    // Recherche d'un client par nom, prénom ou email
    public function rechercher(Request $request)
    {
        $search = $request->input('search');

        $query = DB::table('utilisateur')->where('idStatut', Roles::CLIENT);

        if ($search) {
            $query->where(function ($q) use ($search) {
                $q->where('nom', 'LIKE', "%{$search}%")
                  ->orWhere('prenom', 'LIKE', "%{$search}%")
                  ->orWhere('email', 'LIKE', "%{$search}%");
            });
        }

        $clients = $query->orderBy('nom')->get();

        return view('clients.recherche', compact('clients', 'search'));
    }

    // Dossier complet d'un client : infos, documents, factures, historique de créneaux
    public function dossier($id)
    {
        $client = DB::table('utilisateur')->where('idUtilisateur', $id)->where('idStatut', Roles::CLIENT)->first();

        if (! $client) {
            abort(404);
        }

        $documents = DB::table('document as d')
            ->leftJoin('utilisateur as u', 'd.idAjoutePar', '=', 'u.idUtilisateur')
            ->where('d.idClient', $id)
            ->select('d.*', 'u.nom as ajouteNom', 'u.prenom as ajoutePrenom')
            ->orderByDesc('d.created_at')
            ->get();

        $factures = DB::table('facture')->where('idClient', $id)->orderByDesc('date_emission')->get();

        $rdv = DB::table('rdv as r')
            ->join('utilisateur as u', 'r.idMembre', '=', 'u.idUtilisateur')
            ->join('type_rdv as t', 'r.idTypeRdv', '=', 't.idTypeRdv')
            ->where('r.idClient', $id)
            ->select('r.*', 'u.nom as membreNom', 'u.prenom as membrePrenom', 't.nom as typeNom', 't.couleur as typeCouleur')
            ->orderByDesc('r.date')
            ->get();

        return view('clients.dossier', compact('client', 'documents', 'factures', 'rdv'));
    }

    // Mise à jour du téléphone/adresse d'un client (utile pour les visites à domicile)
    public function modifierContact(Request $request, $id)
    {
        $client = DB::table('utilisateur')->where('idUtilisateur', $id)->where('idStatut', Roles::CLIENT)->first();

        if (! $client) {
            abort(404);
        }

        $request->validate([
            'telephone' => 'nullable|string|max:30',
            'adresse' => 'nullable|string|max:255',
        ]);

        DB::table('utilisateur')->where('idUtilisateur', $id)->update([
            'telephone' => $request->input('telephone'),
            'adresse' => $request->input('adresse'),
        ]);

        return back()->with('confirmation', 'Coordonnées mises à jour.');
    }

    // Ajout d'un document au dossier d'un client
    public function ajouterDocument(Request $request, $id)
    {
        $client = DB::table('utilisateur')->where('idUtilisateur', $id)->where('idStatut', Roles::CLIENT)->first();

        if (! $client) {
            abort(404);
        }

        $request->validate([
            'nom' => 'required|string|max:150',
            'fichier' => 'required|file|max:5120',
        ]);

        $chemin = $request->file('fichier')->store('Stockage/Documents', 'public');

        DB::table('document')->insert([
            'idClient' => $id,
            'idAjoutePar' => Session::get('id'),
            'nom' => $request->input('nom'),
            'chemin' => $chemin,
            'created_at' => now(),
            'updated_at' => now(),
        ]);

        return back()->with('confirmation', 'Document ajouté au dossier.');
    }

    // Ajout d'une facture au dossier d'un client
    public function ajouterFacture(Request $request, $id)
    {
        $client = DB::table('utilisateur')->where('idUtilisateur', $id)->where('idStatut', Roles::CLIENT)->first();

        if (! $client) {
            abort(404);
        }

        $request->validate([
            'montant' => 'required|numeric|min:0',
            'description' => 'required|string|max:500',
            'statut' => 'required|in:en_attente,payee',
            'date_emission' => 'required|date',
            'fichier' => 'nullable|file|mimes:pdf|max:5120',
        ]);

        $chemin = $request->hasFile('fichier')
            ? $request->file('fichier')->store('Stockage/Factures', 'public')
            : null;

        DB::table('facture')->insert([
            'idClient' => $id,
            'idCreePar' => Session::get('id'),
            'montant' => $request->input('montant'),
            'description' => $request->input('description'),
            'statut' => $request->input('statut'),
            'date_emission' => $request->input('date_emission'),
            'chemin' => $chemin,
            'created_at' => now(),
            'updated_at' => now(),
        ]);

        return back()->with('confirmation', 'Facture ajoutée au dossier.');
    }
}
