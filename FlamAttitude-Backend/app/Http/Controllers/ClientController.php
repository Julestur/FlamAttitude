<?php

namespace App\Http\Controllers;

use App\Http\Controllers\Concerns\GereSemaineEdt;
use App\Mail\RdvConfirmationMail;
use App\Support\Planning;
use Illuminate\Database\QueryException;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Mail;
use Illuminate\Support\Facades\Session;

class ClientController extends Controller
{
    use GereSemaineEdt;

    // Mon espace : mes documents et mes factures
    public function accueil()
    {
        $idClient = Session::get('id');

        $documents = DB::table('document')
            ->where('idClient', $idClient)
            ->orderByDesc('created_at')
            ->get();

        $factures = DB::table('facture')
            ->where('idClient', $idClient)
            ->orderByDesc('date_emission')
            ->get();

        return view('mon-espace.accueil', compact('documents', 'factures'));
    }

    // Page EDT de réservation : sélection du type, grille de la semaine, mes RDV
    public function creneaux(Request $request)
    {
        $lundi = $this->lundiDepuisRequete($request);
        $lundiMin = $this->lundiCourant()->toDateString();

        $types = DB::table('type_rdv')->where('actif', true)->orderBy('nom')->get();
        $idTypeRdvSelectionne = (int) ($request->query('type') ?: optional($types->first())->idTypeRdv);

        $jours = $idTypeRdvSelectionne ? Planning::grilleClient($idTypeRdvSelectionne, $lundi) : [];
        $labelSemaine = $this->labelSemaine($lundi);

        $mesRdv = $this->mesRdv();

        return view('mon-espace.creneaux', [
            'jours' => $jours,
            'labelSemaine' => $labelSemaine,
            'lundi' => $lundi->toDateString(),
            'lundiMin' => $lundiMin,
            'types' => $types,
            'idTypeRdvSelectionne' => $idTypeRdvSelectionne,
            'mesRdv' => $mesRdv,
        ]);
    }

    // Partiel AJAX : grille du type + semaine demandés
    public function grille(Request $request)
    {
        $idTypeRdv = (int) $request->query('type');
        $lundi = $this->lundiDepuisRequete($request);
        $jours = $idTypeRdv ? Planning::grilleClient($idTypeRdv, $lundi) : [];

        return view('partials.edt-grille-client', compact('jours'));
    }

    // Réserver un créneau (revalidation serveur avant insertion)
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
            return redirect()->route('mon-espace.creneaux', ['type' => $idTypeRdv])
                ->with('erreur', 'Ce créneau vient d\'être pris ou n\'est plus disponible. Merci d\'en choisir un autre.');
        }

        $heureFin = Planning::heureFinPourType($idTypeRdv, $heure);

        try {
            $idRdv = DB::table('rdv')->insertGetId([
                'idMembre' => $idMembre,
                'idClient' => Session::get('id'),
                'idTypeRdv' => $idTypeRdv,
                'date' => $date,
                'heure_debut' => $heure.':00',
                'heure_fin' => $heureFin.':00',
                'motif' => $request->input('motif'),
                'created_at' => now(),
                'updated_at' => now(),
            ]);
        } catch (QueryException $e) {
            // Contrainte unique (idMembre, date, heure_debut) : un double clic ou une requête
            // concurrente a déjà réservé ce créneau entre la vérification et l'insertion.
            if ($e->getCode() === '23000') {
                return redirect()->route('mon-espace.creneaux', ['type' => $idTypeRdv])
                    ->with('erreur', 'Ce créneau vient d\'être pris ou n\'est plus disponible. Merci d\'en choisir un autre.');
            }

            throw $e;
        }

        try {
            Mail::to(Session::get('email'))->send(new RdvConfirmationMail(Planning::detailsRdv($idRdv)));
        } catch (\Exception $e) {
            report($e);
        }

        return redirect()->route('mon-espace.creneaux', ['type' => $idTypeRdv])->with('confirmation', 'Rendez-vous réservé avec succès. Un email de confirmation vous a été envoyé.');
    }

    // Annuler l'un de mes propres rendez-vous
    public function annuler($id)
    {
        $rdv = DB::table('rdv')->where('idRdv', $id)->where('idClient', Session::get('id'))->first();

        if (! $rdv) {
            abort(403);
        }

        DB::table('rdv')->where('idRdv', $id)->delete();

        return back()->with('confirmation', 'Rendez-vous annulé.');
    }

    private function mesRdv()
    {
        return DB::table('rdv as r')
            ->join('utilisateur as u', 'r.idMembre', '=', 'u.idUtilisateur')
            ->join('type_rdv as t', 'r.idTypeRdv', '=', 't.idTypeRdv')
            ->where('r.idClient', Session::get('id'))
            ->select('r.*', 'u.nom as membreNom', 'u.prenom as membrePrenom', 't.nom as typeNom', 't.couleur as typeCouleur')
            ->orderByDesc('r.date')->orderByDesc('r.heure_debut')
            ->get();
    }
}
