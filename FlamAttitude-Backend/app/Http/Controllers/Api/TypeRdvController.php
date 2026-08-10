<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Support\Roles;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;

/**
 * Pendant API de TypeRdvController (CRUD des types de RDV + affectations membre <-> type),
 * réservé aux admins (middleware role.api:admin appliqué dans routes/api.php).
 */
class TypeRdvController extends Controller
{
    public function index()
    {
        $types = DB::table('type_rdv')->orderBy('nom')->get();
        $membres = DB::table('utilisateur')->where('idStatut', Roles::MEMBRE_ENTREPRISE)
            ->orderBy('nom')
            ->select('idUtilisateur', 'nom', 'prenom')
            ->get();

        $affectations = [];
        DB::table('membre_type_rdv')->get()->each(function ($a) use (&$affectations) {
            $affectations[$a->idMembre][] = $a->idTypeRdv;
        });

        return response()->json(['types' => $types, 'membres' => $membres, 'affectations' => $affectations]);
    }

    private function regles(): array
    {
        return [
            'nom' => 'required|string|max:100',
            'duree_minutes' => 'required|integer|min:5|max:600',
            'couleur' => 'required|regex:/^#[0-9A-Fa-f]{6}$/',
        ];
    }

    public function creer(Request $request)
    {
        $request->validate($this->regles(), [
            'nom.required' => 'Le nom du type est obligatoire.',
            'duree_minutes.required' => 'La durée est obligatoire.',
            'duree_minutes.integer' => 'La durée doit être un nombre de minutes.',
            'couleur.regex' => 'La couleur doit être au format hexadécimal (#RRGGBB).',
        ]);

        $id = DB::table('type_rdv')->insertGetId([
            'nom' => $request->input('nom'),
            'duree_minutes' => $request->input('duree_minutes'),
            'couleur' => $request->input('couleur'),
            'actif' => true,
            'created_at' => now(),
            'updated_at' => now(),
        ]);

        return response()->json(['message' => 'Type de RDV créé.', 'id' => $id]);
    }

    public function modifier(Request $request, $id)
    {
        $request->validate($this->regles());

        DB::table('type_rdv')->where('idTypeRdv', $id)->update([
            'nom' => $request->input('nom'),
            'duree_minutes' => $request->input('duree_minutes'),
            'couleur' => $request->input('couleur'),
            'updated_at' => now(),
        ]);

        return response()->json(['message' => 'Type de RDV modifié.']);
    }

    public function basculerActif($id)
    {
        $type = DB::table('type_rdv')->where('idTypeRdv', $id)->first();

        if (! $type) {
            abort(404);
        }

        DB::table('type_rdv')->where('idTypeRdv', $id)->update(['actif' => ! $type->actif]);

        return response()->json([
            'message' => $type->actif ? 'Type désactivé.' : 'Type réactivé.',
            'actif' => ! $type->actif,
        ]);
    }

    // $request->input('types') attendu : { "idMembre": [idTypeRdv, ...], ... }
    public function enregistrerAffectations(Request $request)
    {
        $idsMembres = DB::table('utilisateur')->where('idStatut', Roles::MEMBRE_ENTREPRISE)->pluck('idUtilisateur');

        DB::table('membre_type_rdv')->whereIn('idMembre', $idsMembres)->delete();

        foreach ($request->input('types', []) as $idMembre => $idsTypes) {
            if (! $idsMembres->contains((int) $idMembre)) {
                continue;
            }

            foreach ($idsTypes as $idType) {
                DB::table('membre_type_rdv')->insert(['idMembre' => (int) $idMembre, 'idTypeRdv' => (int) $idType]);
            }
        }

        return response()->json(['message' => 'Affectations mises à jour.']);
    }
}
