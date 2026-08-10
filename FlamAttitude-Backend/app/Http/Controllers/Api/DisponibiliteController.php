<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Concerns\GereSemaineEdt;
use App\Http\Controllers\Controller;
use App\Support\Planning;
use Carbon\CarbonImmutable;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;

/**
 * Pendant API de DisponibiliteController : grille de disponibilités du membre
 * connecté (jeton Sanctum) et liste de ses RDV à venir ("Mon EDT").
 */
class DisponibiliteController extends Controller
{
    use GereSemaineEdt;

    public function grille(Request $request)
    {
        $idMembre = $request->user()->idUtilisateur;
        $lundi = $this->lundiDepuisRequete($request);

        return response()->json($this->reponseGrille($idMembre, $lundi));
    }

    public function toggle(Request $request)
    {
        $request->validate(['date' => 'required|date', 'heure' => 'required']);

        $idMembre = $request->user()->idUtilisateur;
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

        return response()->json($this->reponseGrille($idMembre, $lundi));
    }

    public function monEdt(Request $request)
    {
        $mesRdv = DB::table('rdv as r')
            ->join('utilisateur as u', 'r.idClient', '=', 'u.idUtilisateur')
            ->join('type_rdv as t', 'r.idTypeRdv', '=', 't.idTypeRdv')
            ->where('r.idMembre', $request->user()->idUtilisateur)
            ->where('r.date', '>=', now()->toDateString())
            ->select(
                'r.*',
                'u.nom as clientNom', 'u.prenom as clientPrenom',
                'u.email as clientEmail', 'u.telephone as clientTelephone', 'u.adresse as clientAdresse',
                't.nom as typeNom', 't.couleur as typeCouleur'
            )
            ->orderBy('r.date')->orderBy('r.heure_debut')
            ->get();

        return response()->json(['mes_rdv' => $mesRdv]);
    }

    public function annulerRdv(Request $request, $id)
    {
        $rdv = DB::table('rdv')->where('idRdv', $id)->where('idMembre', $request->user()->idUtilisateur)->first();

        if (! $rdv) {
            abort(403);
        }

        DB::table('rdv')->where('idRdv', $id)->delete();

        return response()->json(['message' => 'Rendez-vous annulé.']);
    }

    private function reponseGrille(int $idMembre, CarbonImmutable $lundi): array
    {
        $jours = collect(Planning::grilleMembre($idMembre, $lundi))
            ->map(fn ($jour) => ['date' => $jour['date'], 'creneaux' => $jour['creneaux']]);

        return [
            'jours' => $jours,
            'label_semaine' => $this->labelSemaine($lundi),
            'lundi' => $lundi->toDateString(),
            'lundi_min' => $this->lundiCourant()->toDateString(),
        ];
    }
}
