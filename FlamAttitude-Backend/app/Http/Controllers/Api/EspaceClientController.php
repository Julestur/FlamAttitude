<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Concerns\GereSemaineEdt;
use App\Http\Controllers\Controller;
use App\Mail\RdvConfirmationMail;
use App\Support\Planning;
use Illuminate\Database\QueryException;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Mail;

/**
 * Pendant API de ClientController : "mon espace" du client (documents/factures
 * en lecture seule, et réservation/gestion de ses RDV).
 */
class EspaceClientController extends Controller
{
    use GereSemaineEdt;

    public function accueil(Request $request)
    {
        $idClient = $request->user()->idUtilisateur;

        $documents = DB::table('document')
            ->where('idClient', $idClient)
            ->orderByDesc('created_at')
            ->get()
            ->map(fn ($d) => (array) $d + ['url' => url('storage/'.$d->chemin)]);

        $factures = DB::table('facture')
            ->where('idClient', $idClient)
            ->orderByDesc('date_emission')
            ->get()
            ->map(fn ($f) => (array) $f + ['url' => $f->chemin ? url('storage/'.$f->chemin) : null]);

        return response()->json(['documents' => $documents, 'factures' => $factures]);
    }

    // Chargement initial de la page de réservation : types actifs + grille + mes RDV
    public function creneaux(Request $request)
    {
        $lundi = $this->lundiDepuisRequete($request);

        $types = DB::table('type_rdv')->where('actif', true)->orderBy('nom')->get();
        $idTypeRdvSelectionne = (int) ($request->query('type') ?: optional($types->first())->idTypeRdv);

        return response()->json([
            'types' => $types,
            'id_type_rdv_selectionne' => $idTypeRdvSelectionne,
            'jours' => $idTypeRdvSelectionne ? $this->joursSansCarbon(Planning::grilleClient($idTypeRdvSelectionne, $lundi)) : [],
            'label_semaine' => $this->labelSemaine($lundi),
            'lundi' => $lundi->toDateString(),
            'lundi_min' => $this->lundiCourant()->toDateString(),
            'mes_rdv' => $this->mesRdv($request->user()->idUtilisateur),
        ]);
    }

    // Rechargement léger : juste la grille, quand le client change de type ou de semaine
    public function grille(Request $request)
    {
        $idTypeRdv = (int) $request->query('type');
        $lundi = $this->lundiDepuisRequete($request);

        return response()->json([
            'jours' => $idTypeRdv ? $this->joursSansCarbon(Planning::grilleClient($idTypeRdv, $lundi)) : [],
            'label_semaine' => $this->labelSemaine($lundi),
            'lundi' => $lundi->toDateString(),
        ]);
    }

    private function joursSansCarbon(array $jours)
    {
        return collect($jours)->map(fn ($jour) => ['date' => $jour['date'], 'creneaux' => $jour['creneaux']]);
    }

    public function reserver(Request $request)
    {
        $request->validate([
            'idTypeRdv' => 'required|integer|exists:type_rdv,idTypeRdv',
            'date' => 'required|date',
            'heure' => 'required',
            'motif' => 'required|string|max:500',
        ]);

        $idTypeRdv = (int) $request->input('idTypeRdv');
        $date = $request->input('date');
        $heure = $request->input('heure');

        $idMembre = Planning::membreDisponiblePour($idTypeRdv, $date, $heure);

        if (! $idMembre) {
            return response()->json(['message' => 'Ce créneau vient d\'être pris ou n\'est plus disponible. Merci d\'en choisir un autre.'], 409);
        }

        $heureFin = Planning::heureFinPourType($idTypeRdv, $heure);

        try {
            $idRdv = DB::table('rdv')->insertGetId([
                'idMembre' => $idMembre,
                'idClient' => $request->user()->idUtilisateur,
                'idTypeRdv' => $idTypeRdv,
                'date' => $date,
                'heure_debut' => $heure.':00',
                'heure_fin' => $heureFin.':00',
                'motif' => $request->input('motif'),
                'created_at' => now(),
                'updated_at' => now(),
            ]);
        } catch (QueryException $e) {
            if ($e->getCode() === '23000') {
                return response()->json(['message' => 'Ce créneau vient d\'être pris ou n\'est plus disponible. Merci d\'en choisir un autre.'], 409);
            }

            throw $e;
        }

        try {
            Mail::to($request->user()->email)->send(new RdvConfirmationMail(Planning::detailsRdv($idRdv)));
        } catch (\Exception $e) {
            report($e);
        }

        return response()->json(['message' => 'Rendez-vous réservé avec succès. Un email de confirmation vous a été envoyé.']);
    }

    public function annuler(Request $request, $id)
    {
        $rdv = DB::table('rdv')->where('idRdv', $id)->where('idClient', $request->user()->idUtilisateur)->first();

        if (! $rdv) {
            abort(403);
        }

        DB::table('rdv')->where('idRdv', $id)->delete();

        return response()->json(['message' => 'Rendez-vous annulé.']);
    }

    private function mesRdv(int $idClient)
    {
        return DB::table('rdv as r')
            ->join('utilisateur as u', 'r.idMembre', '=', 'u.idUtilisateur')
            ->join('type_rdv as t', 'r.idTypeRdv', '=', 't.idTypeRdv')
            ->where('r.idClient', $idClient)
            ->select('r.*', 'u.nom as membreNom', 'u.prenom as membrePrenom', 't.nom as typeNom', 't.couleur as typeCouleur')
            ->orderByDesc('r.date')->orderByDesc('r.heure_debut')
            ->get();
    }
}
