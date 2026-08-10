<?php

namespace App\Support;

use Carbon\CarbonImmutable;
use Illuminate\Support\Carbon;
use Illuminate\Support\Facades\DB;

/**
 * Calcule les disponibilités de rendez-vous sur une grille hebdomadaire
 * (08h00-18h00, pas de 30 minutes), en tenant compte du type de RDV
 * (durée, membres qualifiés), des disponibilités déclarées et des RDV déjà pris.
 */
class Planning
{
    private const HEURE_OUVERTURE = '08:00';
    private const HEURE_FERMETURE = '18:00';
    private const PAS_MINUTES = 30;

    /**
     * Liste des heures de la grille (08:00, 08:30, ..., 17:30).
     */
    public static function heuresGrille(): array
    {
        $heures = [];
        $t = CarbonImmutable::createFromFormat('H:i', self::HEURE_OUVERTURE);
        $fin = CarbonImmutable::createFromFormat('H:i', self::HEURE_FERMETURE);

        while ($t->lt($fin)) {
            $heures[] = $t->format('H:i');
            $t = $t->addMinutes(self::PAS_MINUTES);
        }

        return $heures;
    }

    /**
     * Grille hebdomadaire côté client : pour un type de RDV donné, chaque case
     * indique si au moins un membre qualifié est libre à cette heure.
     */
    public static function grilleClient(int $idTypeRdv, CarbonImmutable $lundi): array
    {
        $type = DB::table('type_rdv')->where('idTypeRdv', $idTypeRdv)->where('actif', true)->first();
        $dimanche = $lundi->addDays(6);

        $idsMembres = DB::table('membre_type_rdv')->where('idTypeRdv', $idTypeRdv)->pluck('idMembre')->all();

        $dispos = $type && $idsMembres ? self::chargerDisponibilites($idsMembres, $lundi, $dimanche) : [];
        $rdvs = $type && $idsMembres ? self::chargerRdv($idsMembres, $lundi, $dimanche) : [];
        $charges = $type && $idsMembres ? self::chargerCharge($idsMembres) : [];

        $jours = [];
        for ($i = 0; $i < 7; $i++) {
            $date = $lundi->addDays($i);
            $dateStr = $date->toDateString();
            $creneaux = [];

            foreach (self::heuresGrille() as $heure) {
                $creneaux[$heure] = $type && $idsMembres
                    ? self::membreLibre($idsMembres, $dateStr, $heure, (int) $type->duree_minutes, $dispos, $rdvs, $charges) !== null
                    : false;
            }

            $jours[] = ['date' => $dateStr, 'jour' => $date, 'creneaux' => $creneaux];
        }

        return $jours;
    }

    /**
     * Renvoie l'ID d'un membre qualifié et libre pour ce type de RDV à cette date/heure,
     * ou null si aucun (utilisé pour la revalidation au moment de la réservation).
     */
    public static function membreDisponiblePour(int $idTypeRdv, string $date, string $heureDebut): ?int
    {
        $type = DB::table('type_rdv')->where('idTypeRdv', $idTypeRdv)->where('actif', true)->first();

        if (! $type) {
            return null;
        }

        $idsMembres = DB::table('membre_type_rdv')->where('idTypeRdv', $idTypeRdv)->pluck('idMembre')->all();

        if (! $idsMembres) {
            return null;
        }

        $jour = CarbonImmutable::parse($date);
        $dispos = self::chargerDisponibilites($idsMembres, $jour, $jour);
        $rdvs = self::chargerRdv($idsMembres, $jour, $jour);
        $charges = self::chargerCharge($idsMembres);

        return self::membreLibre($idsMembres, $date, $heureDebut, (int) $type->duree_minutes, $dispos, $rdvs, $charges);
    }

    /**
     * Heure de fin correspondant à une heure de début pour un type de RDV donné.
     */
    public static function heureFinPourType(int $idTypeRdv, string $heureDebut): ?string
    {
        $type = DB::table('type_rdv')->where('idTypeRdv', $idTypeRdv)->first();

        return $type ? self::calculerHeureFin($heureDebut, (int) $type->duree_minutes) : null;
    }

    /**
     * Grille hebdomadaire côté membre : pour chaque case, son propre état
     * (indisponible / disponible / réservé avec les infos du RDV).
     */
    public static function grilleMembre(int $idMembre, CarbonImmutable $lundi): array
    {
        $dimanche = $lundi->addDays(6);

        $dispos = self::chargerDisponibilites([$idMembre], $lundi, $dimanche);

        $rdvParDate = [];
        DB::table('rdv as r')
            ->join('utilisateur as u', 'r.idClient', '=', 'u.idUtilisateur')
            ->join('type_rdv as t', 'r.idTypeRdv', '=', 't.idTypeRdv')
            ->where('r.idMembre', $idMembre)
            ->whereBetween('r.date', [$lundi->toDateString(), $dimanche->toDateString()])
            ->select('r.*', 'u.nom as clientNom', 'u.prenom as clientPrenom', 't.nom as typeNom', 't.couleur as typeCouleur')
            ->orderBy('r.heure_debut')
            ->get()
            ->each(function ($rdv) use (&$rdvParDate) {
                $rdvParDate[Carbon::parse($rdv->date)->toDateString()][] = $rdv;
            });

        $jours = [];
        for ($i = 0; $i < 7; $i++) {
            $date = $lundi->addDays($i);
            $dateStr = $date->toDateString();
            $creneaux = [];

            foreach (self::heuresGrille() as $heure) {
                $rdvCorrespondant = null;

                foreach ($rdvParDate[$dateStr] ?? [] as $rdv) {
                    $debut = substr($rdv->heure_debut, 0, 5);
                    $fin = substr($rdv->heure_fin, 0, 5);
                    if ($heure >= $debut && $heure < $fin) {
                        $rdvCorrespondant = $rdv;
                        break;
                    }
                }

                if ($rdvCorrespondant) {
                    $creneaux[$heure] = ['etat' => 'reserve', 'rdv' => $rdvCorrespondant];
                } elseif (isset($dispos["{$idMembre}|{$dateStr}|{$heure}"])) {
                    $creneaux[$heure] = ['etat' => 'disponible'];
                } else {
                    $creneaux[$heure] = ['etat' => 'indisponible'];
                }
            }

            $jours[] = ['date' => $dateStr, 'jour' => $date, 'creneaux' => $creneaux];
        }

        return $jours;
    }

    /**
     * Détails complets d'un RDV (membre, client, type), utilisés pour les emails de confirmation/rappel.
     */
    public static function detailsRdv(int $idRdv): ?object
    {
        return DB::table('rdv as r')
            ->join('utilisateur as membre', 'r.idMembre', '=', 'membre.idUtilisateur')
            ->join('utilisateur as client', 'r.idClient', '=', 'client.idUtilisateur')
            ->join('type_rdv as t', 'r.idTypeRdv', '=', 't.idTypeRdv')
            ->where('r.idRdv', $idRdv)
            ->select(
                'r.*',
                'membre.nom as membreNom', 'membre.prenom as membrePrenom', 'membre.email as membreEmail', 'membre.fcm_token as membreFcmToken',
                'client.nom as clientNom', 'client.prenom as clientPrenom', 'client.email as clientEmail', 'client.fcm_token as clientFcmToken',
                't.nom as typeNom', 't.couleur as typeCouleur', 't.duree_minutes as typeDureeMinutes'
            )
            ->first();
    }

    /**
     * Un RDV chevauche-t-il déjà cette case de 30 minutes pour ce membre ?
     * (empêche de retirer une disponibilité déjà couverte par une réservation)
     */
    public static function estReserve(int $idMembre, string $date, string $heure): bool
    {
        $heureFin = CarbonImmutable::createFromFormat('H:i', $heure)->addMinutes(self::PAS_MINUTES)->format('H:i');

        return DB::table('rdv')
            ->where('idMembre', $idMembre)
            ->where('date', $date)
            ->where('heure_debut', '<', $heureFin.':00')
            ->where('heure_fin', '>', $heure.':00')
            ->exists();
    }

    /**
     * Un membre donné propose-t-il ce type de RDV ?
     */
    public static function estQualifiePour(int $idMembre, int $idTypeRdv): bool
    {
        return DB::table('membre_type_rdv')->where('idMembre', $idMembre)->where('idTypeRdv', $idTypeRdv)->exists();
    }

    /**
     * Un membre donné est-il libre (disponibilités + pas de chevauchement avec un
     * autre RDV) pour ce créneau ? Utilisé par l'admin pour déplacer/réassigner un
     * RDV existant ; [$idRdvExclu] ignore le RDV qu'on est justement en train de
     * déplacer, sinon il se bloquerait lui-même.
     */
    public static function membreLibrePourDeplacement(int $idMembre, string $date, string $heureDebut, int $dureeMinutes, ?int $idRdvExclu = null): bool
    {
        $heureFin = self::calculerHeureFin($heureDebut, $dureeMinutes);

        if ($heureFin > self::HEURE_FERMETURE) {
            return false;
        }

        foreach (self::marquesRequises($heureDebut, $dureeMinutes) as $marque) {
            $dispo = DB::table('disponibilite')
                ->where('idMembre', $idMembre)->where('date', $date)->where('heure_debut', $marque.':00')
                ->exists();

            if (! $dispo) {
                return false;
            }
        }

        $chevauche = DB::table('rdv')
            ->where('idMembre', $idMembre)
            ->where('date', $date)
            ->where('heure_debut', '<', $heureFin.':00')
            ->where('heure_fin', '>', $heureDebut.':00')
            ->when($idRdvExclu, fn ($q) => $q->where('idRdv', '!=', $idRdvExclu))
            ->exists();

        return ! $chevauche;
    }

    /**
     * Parmi les membres qualifiés, renvoie celui qui est libre à ce créneau ET qui a
     * le moins de RDV à venir (répartition équilibrée de la charge de travail),
     * plutôt que systématiquement le premier de la liste.
     */
    private static function membreLibre(array $idsMembres, string $date, string $heureDebut, int $dureeMinutes, array $dispos, array $rdvs, array $charges = []): ?int
    {
        $heureFin = self::calculerHeureFin($heureDebut, $dureeMinutes);

        if ($heureFin > self::HEURE_FERMETURE) {
            return null;
        }

        $marques = self::marquesRequises($heureDebut, $dureeMinutes);
        $candidats = [];

        foreach ($idsMembres as $idMembre) {
            $toutesLibres = true;

            foreach ($marques as $marque) {
                if (! isset($dispos["{$idMembre}|{$date}|{$marque}"])) {
                    $toutesLibres = false;
                    break;
                }
            }

            if (! $toutesLibres) {
                continue;
            }

            $chevauche = false;

            foreach ($rdvs[$idMembre][$date] ?? [] as $intervalle) {
                if ($heureDebut < $intervalle['fin'] && $heureFin > $intervalle['debut']) {
                    $chevauche = true;
                    break;
                }
            }

            if (! $chevauche) {
                $candidats[] = $idMembre;
            }
        }

        if (! $candidats) {
            return null;
        }

        usort($candidats, fn ($a, $b) => ($charges[$a] ?? 0) <=> ($charges[$b] ?? 0) ?: $a <=> $b);

        return $candidats[0];
    }

    /**
     * Nombre de RDV à venir (à partir d'aujourd'hui) par membre, pour équilibrer
     * l'assignation automatique entre les employés qualifiés pour un type de RDV.
     */
    private static function chargerCharge(array $idsMembres): array
    {
        return DB::table('rdv')
            ->whereIn('idMembre', $idsMembres)
            ->where('date', '>=', now()->toDateString())
            ->selectRaw('idMembre, count(*) as total')
            ->groupBy('idMembre')
            ->pluck('total', 'idMembre')
            ->all();
    }

    private static function marquesRequises(string $heureDebut, int $dureeMinutes): array
    {
        $marques = [];
        $t = CarbonImmutable::createFromFormat('H:i', $heureDebut);
        $nb = (int) ceil($dureeMinutes / self::PAS_MINUTES);

        for ($i = 0; $i < $nb; $i++) {
            $marques[] = $t->addMinutes($i * self::PAS_MINUTES)->format('H:i');
        }

        return $marques;
    }

    private static function calculerHeureFin(string $heureDebut, int $dureeMinutes): string
    {
        return CarbonImmutable::createFromFormat('H:i', $heureDebut)->addMinutes($dureeMinutes)->format('H:i');
    }

    private static function chargerDisponibilites(array $idsMembres, CarbonImmutable $lundi, CarbonImmutable $dimanche): array
    {
        $set = [];

        DB::table('disponibilite')
            ->whereIn('idMembre', $idsMembres)
            ->whereBetween('date', [$lundi->toDateString(), $dimanche->toDateString()])
            ->get(['idMembre', 'date', 'heure_debut'])
            ->each(function ($r) use (&$set) {
                $date = Carbon::parse($r->date)->toDateString();
                $heure = substr($r->heure_debut, 0, 5);
                $set["{$r->idMembre}|{$date}|{$heure}"] = true;
            });

        return $set;
    }

    private static function chargerRdv(array $idsMembres, CarbonImmutable $lundi, CarbonImmutable $dimanche): array
    {
        $parMembreEtDate = [];

        DB::table('rdv')
            ->whereIn('idMembre', $idsMembres)
            ->whereBetween('date', [$lundi->toDateString(), $dimanche->toDateString()])
            ->get(['idMembre', 'date', 'heure_debut', 'heure_fin'])
            ->each(function ($r) use (&$parMembreEtDate) {
                $date = Carbon::parse($r->date)->toDateString();
                $parMembreEtDate[$r->idMembre][$date][] = [
                    'debut' => substr($r->heure_debut, 0, 5),
                    'fin' => substr($r->heure_fin, 0, 5),
                ];
            });

        return $parMembreEtDate;
    }
}
