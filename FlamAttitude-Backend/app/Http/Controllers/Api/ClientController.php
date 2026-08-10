<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Support\Roles;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;

/**
 * Pendant API de StaffClientController : recherche et dossier client (admin/staff),
 * en JSON, pour l'utilisateur authentifié par jeton Sanctum.
 */
class ClientController extends Controller
{
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

        $clients = $query->orderBy('nom')
            ->select('idUtilisateur', 'nom', 'prenom', 'email', 'telephone')
            ->get();

        return response()->json(['clients' => $clients]);
    }

    public function dossier($id)
    {
        $client = DB::table('utilisateur')->where('idUtilisateur', $id)->where('idStatut', Roles::CLIENT)
            ->select('idUtilisateur', 'nom', 'prenom', 'email', 'identifiant', 'telephone', 'adresse', 'pdp')
            ->first();

        if (! $client) {
            abort(404);
        }

        $documents = DB::table('document as d')
            ->leftJoin('utilisateur as u', 'd.idAjoutePar', '=', 'u.idUtilisateur')
            ->where('d.idClient', $id)
            ->select('d.*', 'u.nom as ajouteNom', 'u.prenom as ajoutePrenom')
            ->orderByDesc('d.created_at')
            ->get()
            ->map(fn ($d) => (array) $d + ['url' => url('storage/'.$d->chemin)]);

        $factures = DB::table('facture')->where('idClient', $id)->orderByDesc('date_emission')->get()
            ->map(fn ($f) => (array) $f + ['url' => $f->chemin ? url('storage/'.$f->chemin) : null]);

        $rdv = DB::table('rdv as r')
            ->join('utilisateur as u', 'r.idMembre', '=', 'u.idUtilisateur')
            ->join('type_rdv as t', 'r.idTypeRdv', '=', 't.idTypeRdv')
            ->where('r.idClient', $id)
            ->select('r.*', 'u.nom as membreNom', 'u.prenom as membrePrenom', 't.nom as typeNom', 't.couleur as typeCouleur')
            ->orderByDesc('r.date')
            ->get();

        return response()->json([
            'client' => $client,
            'documents' => $documents,
            'factures' => $factures,
            'rdv' => $rdv,
        ]);
    }

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

        return response()->json(['message' => 'Coordonnées mises à jour.']);
    }

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
            'idAjoutePar' => $request->user()->idUtilisateur,
            'nom' => $request->input('nom'),
            'chemin' => $chemin,
            'created_at' => now(),
            'updated_at' => now(),
        ]);

        return response()->json(['message' => 'Document ajouté au dossier.', 'url' => url('storage/'.$chemin)]);
    }

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
            'idCreePar' => $request->user()->idUtilisateur,
            'montant' => $request->input('montant'),
            'description' => $request->input('description'),
            'statut' => $request->input('statut'),
            'date_emission' => $request->input('date_emission'),
            'chemin' => $chemin,
            'created_at' => now(),
            'updated_at' => now(),
        ]);

        return response()->json(['message' => 'Facture ajoutée au dossier.']);
    }
}
