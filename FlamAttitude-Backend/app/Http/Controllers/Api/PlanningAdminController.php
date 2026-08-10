<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Concerns\GereSemaineEdt;
use App\Http\Controllers\Controller;
use App\Mail\RdvAnnuleMail;
use App\Mail\RdvDeplaceMail;
use App\Support\Planning;
use App\Support\Roles;
use Illuminate\Database\QueryException;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Mail;

/**
 * Vue "planning global" réservée à l'admin : EDT de n'importe quel employé,
 * avec possibilité de déplacer/réassigner ou annuler n'importe quel RDV
 * (contrairement à DisponibiliteController/EspaceClientController, limités
 * au staff/client propriétaire du RDV).
 */
class PlanningAdminController extends Controller
{
    use GereSemaineEdt;

    public function membres()
    {
        $membres = DB::table('utilisateur')->where('idStatut', Roles::MEMBRE_ENTREPRISE)
            ->orderBy('nom')
            ->select('idUtilisateur', 'nom', 'prenom')
            ->get();

        return response()->json(['membres' => $membres]);
    }

    public function grilleMembre(Request $request, $id)
    {
        $membre = DB::table('utilisateur')->where('idUtilisateur', $id)->where('idStatut', Roles::MEMBRE_ENTREPRISE)->first();

        if (! $membre) {
            abort(404);
        }

        $lundi = $this->lundiDepuisRequete($request);
        $jours = collect(Planning::grilleMembre((int) $id, $lundi))
            ->map(fn ($jour) => ['date' => $jour['date'], 'creneaux' => $jour['creneaux']]);

        return response()->json([
            'jours' => $jours,
            'label_semaine' => $this->labelSemaine($lundi),
            'lundi' => $lundi->toDateString(),
            'lundi_min' => $this->lundiCourant()->toDateString(),
        ]);
    }

    // Déplace/réassigne un RDV existant vers un autre employé et/ou un autre créneau.
    public function deplacerRdv(Request $request, $id)
    {
        $request->validate([
            'id_membre' => 'required|integer',
            'date' => 'required|date',
            'heure' => 'required',
        ]);

        $rdv = DB::table('rdv')->where('idRdv', $id)->first();

        if (! $rdv) {
            abort(404);
        }

        $idMembre = (int) $request->input('id_membre');
        $date = $request->input('date');
        $heure = $request->input('heure');

        $membre = DB::table('utilisateur')->where('idUtilisateur', $idMembre)->where('idStatut', Roles::MEMBRE_ENTREPRISE)->first();

        if (! $membre) {
            return response()->json(['message' => 'Employé introuvable.'], 404);
        }

        if (! Planning::estQualifiePour($idMembre, $rdv->idTypeRdv)) {
            return response()->json(['message' => 'Cet employé ne propose pas ce type de rendez-vous.'], 422);
        }

        $type = DB::table('type_rdv')->where('idTypeRdv', $rdv->idTypeRdv)->first();
        $heureFin = Planning::heureFinPourType($rdv->idTypeRdv, $heure);

        if (! Planning::membreLibrePourDeplacement($idMembre, $date, $heure, (int) $type->duree_minutes, (int) $id)) {
            return response()->json(['message' => 'Cet employé n\'est pas disponible sur ce créneau.'], 409);
        }

        try {
            DB::table('rdv')->where('idRdv', $id)->update([
                'idMembre' => $idMembre,
                'date' => $date,
                'heure_debut' => $heure.':00',
                'heure_fin' => $heureFin.':00',
                'updated_at' => now(),
            ]);
        } catch (QueryException $e) {
            if ($e->getCode() === '23000') {
                return response()->json(['message' => 'Ce créneau vient d\'être pris. Merci d\'en choisir un autre.'], 409);
            }

            throw $e;
        }

        $details = Planning::detailsRdv((int) $id);

        try {
            Mail::to($details->clientEmail)->send(new RdvDeplaceMail($details, pourMembre: false));
            Mail::to($details->membreEmail)->send(new RdvDeplaceMail($details, pourMembre: true));
        } catch (\Exception $e) {
            report($e);
        }

        return response()->json(['message' => 'Rendez-vous déplacé.']);
    }

    public function annulerRdv($id)
    {
        $details = Planning::detailsRdv((int) $id);

        if (! $details) {
            abort(404);
        }

        DB::table('rdv')->where('idRdv', $id)->delete();

        try {
            Mail::to($details->clientEmail)->send(new RdvAnnuleMail($details, pourMembre: false));
            Mail::to($details->membreEmail)->send(new RdvAnnuleMail($details, pourMembre: true));
        } catch (\Exception $e) {
            report($e);
        }

        return response()->json(['message' => 'Rendez-vous annulé.']);
    }
}
