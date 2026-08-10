<?php

namespace App\Console\Commands;

use App\Mail\RdvRappelMail;
use App\Support\Notificateur;
use App\Support\Planning;
use Illuminate\Console\Attributes\Description;
use Illuminate\Console\Attributes\Signature;
use Illuminate\Console\Command;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Mail;

#[Signature('rdv:rappels')]
#[Description('Envoie les rappels par email pour les RDV dans une semaine et pour ceux du lendemain')]
class EnvoyerRappelsRdv extends Command
{
    /**
     * Execute the console command.
     */
    public function handle()
    {
        $this->envoyerPour(now()->addWeek()->toDateString(), 'rappel_semaine_envoye', 'dans une semaine');
        $this->envoyerPour(now()->addDay()->toDateString(), 'rappel_veille_envoye', 'demain');
    }

    private function envoyerPour(string $date, string $colonne, string $delaiLabel): void
    {
        $idsRdv = DB::table('rdv')->where('date', $date)->where($colonne, false)->pluck('idRdv');

        foreach ($idsRdv as $idRdv) {
            $details = Planning::detailsRdv($idRdv);

            if (! $details) {
                continue;
            }

            try {
                Mail::to($details->clientEmail)->send(new RdvRappelMail($details, $delaiLabel));
                Notificateur::envoyer(
                    $details->clientFcmToken,
                    'Rappel de rendez-vous',
                    "{$details->typeNom} {$delaiLabel} à ".substr($details->heure_debut, 0, 5)
                );
                DB::table('rdv')->where('idRdv', $idRdv)->update([$colonne => true]);
                $this->info("Rappel envoyé pour le RDV #{$idRdv} ({$delaiLabel}).");
            } catch (\Exception $e) {
                report($e);
                $this->error("Échec de l'envoi du rappel pour le RDV #{$idRdv} : {$e->getMessage()}");
            }
        }
    }
}
