<?php

namespace App\Http\Controllers;

use App\Http\Controllers\Concerns\GereSemaineEdt;
use App\Support\Planning;
use Carbon\CarbonImmutable;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Session;

class DisponibiliteController extends Controller
{
    use GereSemaineEdt;

    // Page "Gérer mes disponibilités" : ma grille de créneaux libres/occupés
    public function index(Request $request)
    {
        $idMembre = Session::get('id');
        $lundi = $this->lundiDepuisRequete($request);

        $jours = Planning::grilleMembre($idMembre, $lundi);
        $labelSemaine = $this->labelSemaine($lundi);
        $lundiMin = $this->lundiCourant()->toDateString();

        return view('creneaux.gestion', [
            'jours' => $jours,
            'labelSemaine' => $labelSemaine,
            'lundi' => $lundi->toDateString(),
            'lundiMin' => $lundiMin,
        ]);
    }

    // Page "Mon EDT" : liste de mes RDV à venir, avec toutes les infos client au clic
    public function monEdt()
    {
        $mesRdv = $this->mesRdvAVenir(Session::get('id'));

        return view('creneaux.mon-edt', compact('mesRdv'));
    }

    // Partiel AJAX : grille de la semaine demandée
    public function grille(Request $request)
    {
        $idMembre = Session::get('id');
        $lundi = $this->lundiDepuisRequete($request);
        $jours = Planning::grilleMembre($idMembre, $lundi);

        return view('partials.edt-grille-membre', compact('jours'));
    }

    // Bascule une case de 30 min : dispo <-> indispo (refuse si déjà réservée)
    public function toggle(Request $request)
    {
        $request->validate(['date' => 'required|date', 'heure' => 'required']);

        $idMembre = Session::get('id');
        $date = $request->input('date');
        $heure = $request->input('heure');

        if (! Planning::estReserve($idMembre, $date, $heure)) {
            $existe = DB::table('disponibilite')
                ->where('idMembre', $idMembre)->where('date', $date)->where('heure_debut', $heure.':00')
                ->exists();

            if ($existe) {
                DB::table('disponibilite')->where('idMembre', $idMembre)->where('date', $date)->where('heure_debut', $heure.':00')->delete();
            } else {
                $heureFin = CarbonImmutable::createFromFormat('H:i', $heure)->addMinutes(30)->format('H:i');
                DB::table('disponibilite')->insert([
                    'idMembre' => $idMembre,
                    'date' => $date,
                    'heure_debut' => $heure.':00',
                    'heure_fin' => $heureFin.':00',
                    'created_at' => now(),
                    'updated_at' => now(),
                ]);
            }
        }

        $lundi = $this->lundiDepuisRequete($request);
        $jours = Planning::grilleMembre($idMembre, $lundi);

        return view('partials.edt-grille-membre', compact('jours'));
    }

    // Annule un RDV sur mon planning
    public function annulerRdv(Request $request, $id)
    {
        $rdv = DB::table('rdv')->where('idRdv', $id)->where('idMembre', Session::get('id'))->first();

        if (! $rdv) {
            abort(403);
        }

        DB::table('rdv')->where('idRdv', $id)->delete();

        if ($request->ajax()) {
            return response('', 204);
        }

        return back()->with('confirmation', 'Rendez-vous annulé.');
    }

    private function mesRdvAVenir(int $idMembre)
    {
        return DB::table('rdv as r')
            ->join('utilisateur as u', 'r.idClient', '=', 'u.idUtilisateur')
            ->join('type_rdv as t', 'r.idTypeRdv', '=', 't.idTypeRdv')
            ->where('r.idMembre', $idMembre)
            ->where('r.date', '>=', now()->toDateString())
            ->select(
                'r.*',
                'u.nom as clientNom', 'u.prenom as clientPrenom',
                'u.email as clientEmail', 'u.telephone as clientTelephone', 'u.adresse as clientAdresse',
                't.nom as typeNom', 't.couleur as typeCouleur'
            )
            ->orderBy('r.date')->orderBy('r.heure_debut')
            ->get();
    }
}
