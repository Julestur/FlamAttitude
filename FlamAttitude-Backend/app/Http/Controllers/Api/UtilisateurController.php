<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Support\ReglesMotDePasse;
use App\Support\Roles;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Hash;

/**
 * Pendant API de OPTION_ajoutUtilisateurController / OPTION_suppressionUtilisateurController,
 * réservé aux admins (middleware role.api:admin appliqué dans routes/api.php).
 */
class UtilisateurController extends Controller
{
    public function creer(Request $request)
    {
        $request->validate([
            'nom' => 'required|string|max:255',
            'prenom' => 'required|string|max:255',
            'email' => 'required|email|unique:utilisateur,email',
            'identifiant' => 'required|unique:utilisateur,identifiant',
            'mot_de_passe' => ['required', 'confirmed', ReglesMotDePasse::politique()],
            'grade' => 'required|in:Admin,Membre_Entreprise,Client',
        ], [
            'email.unique' => 'Cet email est déjà utilisé.',
            'identifiant.unique' => 'Cet identifiant est déjà pris.',
        ]);

        $statuts = [
            'Admin' => Roles::ADMIN,
            'Membre_Entreprise' => Roles::MEMBRE_ENTREPRISE,
            'Client' => Roles::CLIENT,
        ];

        $id = DB::table('utilisateur')->insertGetId([
            'nom' => $request->input('nom'),
            'prenom' => $request->input('prenom'),
            'email' => $request->input('email'),
            'identifiant' => $request->input('identifiant'),
            'mdp' => Hash::make($request->input('mot_de_passe')),
            'pdp' => 'profil.png',
            'idStatut' => $statuts[$request->input('grade')],
            'estVerif' => 1,
        ]);

        return response()->json(['message' => 'Utilisateur créé.', 'id' => $id]);
    }

    // Liste des membres/clients supprimables (jamais les admins), avec recherche optionnelle.
    public function listerPourSuppression(Request $request)
    {
        $search = $request->input('search');

        $query = DB::table('utilisateur as u')
            ->join('statut as s', 'u.idStatut', '=', 's.idStatut')
            ->select('u.idUtilisateur', 'u.nom', 'u.prenom', 'u.identifiant', 's.libelle as grade')
            ->whereIn('u.idStatut', [Roles::MEMBRE_ENTREPRISE, Roles::CLIENT]);

        if ($search) {
            $query->where(function ($q) use ($search) {
                $q->where('u.nom', 'LIKE', "%{$search}%")
                    ->orWhere('u.prenom', 'LIKE', "%{$search}%")
                    ->orWhere('u.identifiant', 'LIKE', "%{$search}%");
            });
        }

        return response()->json(['utilisateurs' => $query->orderBy('u.nom')->get()]);
    }

    public function supprimer(Request $request, $id)
    {
        if ((int) $id === $request->user()->idUtilisateur) {
            return response()->json(['message' => 'Vous ne pouvez pas supprimer votre propre compte.'], 422);
        }

        $cible = DB::table('utilisateur')->where('idUtilisateur', $id)
            ->whereIn('idStatut', [Roles::MEMBRE_ENTREPRISE, Roles::CLIENT])
            ->first();

        if (! $cible) {
            return response()->json(['message' => 'Utilisateur introuvable.'], 404);
        }

        DB::table('document')->where('idAjoutePar', $id)->update(['idAjoutePar' => null]);
        DB::table('facture')->where('idCreePar', $id)->update(['idCreePar' => null]);

        DB::table('document')->where('idClient', $id)->delete();
        DB::table('facture')->where('idClient', $id)->delete();
        DB::table('rdv')->where('idClient', $id)->delete();
        DB::table('rdv')->where('idMembre', $id)->delete();

        DB::table('utilisateur')->where('idUtilisateur', $id)->delete();

        return response()->json(['message' => 'Suppression effectuée avec succès.']);
    }
}
