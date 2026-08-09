<?php

namespace App\Http\Controllers;

use App\Support\Roles;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;

class TypeRdvController extends Controller
{
    public function index()
    {
        $types = DB::table('type_rdv')->orderBy('nom')->get();
        $membres = DB::table('utilisateur')->where('idStatut', Roles::MEMBRE_ENTREPRISE)->orderBy('nom')->get();

        $affectations = [];
        DB::table('membre_type_rdv')->get()->each(function ($a) use (&$affectations) {
            $affectations[$a->idMembre][] = $a->idTypeRdv;
        });

        return view('typesRdv.gestion', compact('types', 'membres', 'affectations'));
    }

    // Enregistre, pour chaque employé, la liste des types de RDV qu'il peut prendre en charge
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

        return back()->with('confirmation', 'Affectations mises à jour.');
    }

    public function creer(Request $request)
    {
        $request->validate([
            'nom' => 'required|string|max:100',
            'duree_minutes' => 'required|integer|min:5|max:600',
            'couleur' => 'required|regex:/^#[0-9A-Fa-f]{6}$/',
        ], [
            'nom.required' => 'Le nom du type est obligatoire.',
            'duree_minutes.required' => 'La durée est obligatoire.',
            'duree_minutes.integer' => 'La durée doit être un nombre de minutes.',
            'couleur.regex' => 'La couleur doit être au format hexadécimal (#RRGGBB).',
        ]);

        DB::table('type_rdv')->insert([
            'nom' => $request->input('nom'),
            'duree_minutes' => $request->input('duree_minutes'),
            'couleur' => $request->input('couleur'),
            'actif' => true,
            'created_at' => now(),
            'updated_at' => now(),
        ]);

        return back()->with('confirmation', 'Type de RDV créé.');
    }

    public function modifier(Request $request, $id)
    {
        $request->validate([
            'nom' => 'required|string|max:100',
            'duree_minutes' => 'required|integer|min:5|max:600',
            'couleur' => 'required|regex:/^#[0-9A-Fa-f]{6}$/',
        ]);

        DB::table('type_rdv')->where('idTypeRdv', $id)->update([
            'nom' => $request->input('nom'),
            'duree_minutes' => $request->input('duree_minutes'),
            'couleur' => $request->input('couleur'),
            'updated_at' => now(),
        ]);

        return back()->with('confirmation', 'Type de RDV modifié.');
    }

    public function basculerActif($id)
    {
        $type = DB::table('type_rdv')->where('idTypeRdv', $id)->first();

        if (! $type) {
            abort(404);
        }

        DB::table('type_rdv')->where('idTypeRdv', $id)->update(['actif' => ! $type->actif]);

        return back()->with('confirmation', $type->actif ? 'Type désactivé.' : 'Type réactivé.');
    }
}
