<?php

namespace Database\Seeders;

use Illuminate\Database\Console\Seeds\WithoutModelEvents;
use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Facades\Storage;

class DatabaseSeeder extends Seeder
{
    use WithoutModelEvents;

    /**
     * Seed the application's database.
     */
    public function run(): void
    {
        // --------------------------------------------------------------------------------------------------------
        // ------------ STATUTS (RÔLES) ----------------------------------------------------------------------------
        // --------------------------------------------------------------------------------------------------------

        DB::table('statut')->insert([
            ['idStatut' => 1, 'libelle' => 'Admin'],
            ['idStatut' => 2, 'libelle' => 'Membre de l\'entreprise'],
            ['idStatut' => 3, 'libelle' => 'Client'],
        ]);

        // --------------------------------------------------------------------------------------------------------
        // ------------ UTILISATEURS --------------------------------------------------------------------------------
        // --------------------------------------------------------------------------------------------------------

        // ADMIN
        DB::table('utilisateur')->insert([
            'nom' => 'Admin',
            'prenom' => 'Admin',
            'email' => 'ju2ju@outlook.fr',
            'identifiant' => 'Admin',
            'mdp' => Hash::make('Admin!'),
            'idStatut' => 1,
            'pdp' => 'profil.png',
            'estVerif' => 1,
        ]);

        // MEMBRES DE L'ENTREPRISE
        DB::table('utilisateur')->insert([
            'nom' => 'Martin',
            'prenom' => 'Julie',
            'email' => 'Turchi.Jules@outlook.fr',
            'identifiant' => 'user',
            'mdp' => Hash::make('user'),
            'idStatut' => 2,
            'pdp' => 'profil.png',
            'estVerif' => 1,
        ]);

        DB::table('utilisateur')->insert([
            'nom' => 'Haddad',
            'prenom' => 'Karim',
            'email' => 'karim.haddad@flamattitude.fr',
            'identifiant' => 'karim',
            'mdp' => Hash::make('Membre123!'),
            'idStatut' => 2,
            'pdp' => 'profil.png',
            'estVerif' => 1,
        ]);

        // CLIENTS
        DB::table('utilisateur')->insert([
            'nom' => 'Bernard',
            'prenom' => 'Sophie',
            'email' => 'ju2ju32@gmail.com',
            'identifiant' => 'client',
            'mdp' => Hash::make('client'),
            'idStatut' => 3,
            'pdp' => 'profil.png',
            'estVerif' => 1,
        ]);

        DB::table('utilisateur')->insert([
            'nom' => 'Lefèvre',
            'prenom' => 'Marc',
            'email' => 'marc.lefevre@client.fr',
            'identifiant' => 'marc',
            'mdp' => Hash::make('Client123!'),
            'idStatut' => 3,
            'pdp' => 'profil.png',
            'estVerif' => 1,
        ]);

        DB::table('utilisateur')->insert([
            'nom' => 'Costa',
            'prenom' => 'Nina',
            'email' => 'nina.costa@client.fr',
            'identifiant' => 'nina',
            'mdp' => Hash::make('Client123!'),
            'idStatut' => 3,
            'pdp' => 'profil.png',
            'estVerif' => 1,
        ]);

        $idJulie = DB::table('utilisateur')->where('identifiant', 'user')->value('idUtilisateur');
        $idKarim = DB::table('utilisateur')->where('identifiant', 'karim')->value('idUtilisateur');
        $idSophie = DB::table('utilisateur')->where('identifiant', 'client')->value('idUtilisateur');
        $idMarc = DB::table('utilisateur')->where('identifiant', 'marc')->value('idUtilisateur');

        // --------------------------------------------------------------------------------------------------------
        // ------------ TYPES DE RDV ---------------------------------------------------------------------------------
        // --------------------------------------------------------------------------------------------------------

        DB::table('type_rdv')->insert([
            ['nom' => 'Maintenance', 'duree_minutes' => 60, 'couleur' => '#5988DA', 'actif' => true, 'created_at' => now(), 'updated_at' => now()],
            ['nom' => 'Ramonage', 'duree_minutes' => 90, 'couleur' => '#e67e22', 'actif' => true, 'created_at' => now(), 'updated_at' => now()],
            ['nom' => 'Dépannage', 'duree_minutes' => 45, 'couleur' => '#e74c3c', 'actif' => true, 'created_at' => now(), 'updated_at' => now()],
            ['nom' => 'Autre', 'duree_minutes' => 30, 'couleur' => '#7f8c8d', 'actif' => true, 'created_at' => now(), 'updated_at' => now()],
        ]);

        $idMaintenance = DB::table('type_rdv')->where('nom', 'Maintenance')->value('idTypeRdv');
        $idRamonage = DB::table('type_rdv')->where('nom', 'Ramonage')->value('idTypeRdv');
        $idDepannage = DB::table('type_rdv')->where('nom', 'Dépannage')->value('idTypeRdv');
        $idAutre = DB::table('type_rdv')->where('nom', 'Autre')->value('idTypeRdv');

        // Spécialisation des membres : Julie fait Maintenance + Autre, Karim fait Ramonage + Dépannage + Autre
        DB::table('membre_type_rdv')->insert([
            ['idMembre' => $idJulie, 'idTypeRdv' => $idMaintenance],
            ['idMembre' => $idJulie, 'idTypeRdv' => $idAutre],
            ['idMembre' => $idKarim, 'idTypeRdv' => $idRamonage],
            ['idMembre' => $idKarim, 'idTypeRdv' => $idDepannage],
            ['idMembre' => $idKarim, 'idTypeRdv' => $idAutre],
        ]);

        // --------------------------------------------------------------------------------------------------------
        // ------------ DISPONIBILITÉS ET RDV DE DÉMONSTRATION ----------------------------------------------------
        // --------------------------------------------------------------------------------------------------------

        $this->seedDisponibilites($idJulie, now()->addDays(1)->toDateString(), '09:00', '12:00');
        $this->seedDisponibilites($idJulie, now()->addDays(2)->toDateString(), '14:00', '17:00');
        $this->seedDisponibilites($idKarim, now()->addDays(1)->toDateString(), '14:00', '17:00');
        $this->seedDisponibilites($idKarim, now()->addDays(3)->toDateString(), '09:00', '11:00');

        // RDV déjà réservé : Ramonage (90 min) avec Karim, dans sa plage 14h-17h du lendemain
        DB::table('rdv')->insert([
            'idMembre' => $idKarim,
            'idClient' => $idSophie,
            'idTypeRdv' => $idRamonage,
            'date' => now()->addDays(1)->toDateString(),
            'heure_debut' => '15:00:00',
            'heure_fin' => '16:30:00',
            'motif' => 'Suivi de dossier',
            'created_at' => now(),
            'updated_at' => now(),
        ]);

        // --------------------------------------------------------------------------------------------------------
        // ------------ DOCUMENTS ET FACTURES DE DÉMONSTRATION --------------------------------------------------
        // --------------------------------------------------------------------------------------------------------

        $cheminDemo = Storage::disk('public')->putFile('Stockage/Documents', $this->fichierDemo());

        DB::table('document')->insert([
            'idClient' => $idSophie,
            'idAjoutePar' => $idJulie,
            'nom' => 'Exemple de document',
            'chemin' => $cheminDemo,
        ]);

        DB::table('facture')->insert([
            'idClient' => $idSophie,
            'idCreePar' => $idJulie,
            'montant' => 150.00,
            'description' => 'Consultation initiale',
            'statut' => 'payee',
            'date_emission' => now()->subDays(5)->toDateString(),
        ]);

        DB::table('facture')->insert([
            'idClient' => $idMarc,
            'idCreePar' => $idKarim,
            'montant' => 320.50,
            'description' => 'Prestation mensuelle',
            'statut' => 'en_attente',
            'date_emission' => now()->subDays(1)->toDateString(),
        ]);
    }

    /**
     * Insère une ligne de disponibilité de 30 minutes pour chaque tranche entre $debut et $fin (exclu).
     */
    private function seedDisponibilites(int $idMembre, string $date, string $debut, string $fin): void
    {
        $t = \Carbon\CarbonImmutable::createFromFormat('H:i', $debut);
        $limite = \Carbon\CarbonImmutable::createFromFormat('H:i', $fin);

        while ($t->lt($limite)) {
            $heureFin = $t->addMinutes(30);

            DB::table('disponibilite')->insert([
                'idMembre' => $idMembre,
                'date' => $date,
                'heure_debut' => $t->format('H:i:s'),
                'heure_fin' => $heureFin->format('H:i:s'),
                'created_at' => now(),
                'updated_at' => now(),
            ]);

            $t = $heureFin;
        }
    }

    private function fichierDemo(): \Illuminate\Http\UploadedFile
    {
        $chemin = tempnam(sys_get_temp_dir(), 'demo') . '.txt';
        file_put_contents($chemin, "Document de démonstration Flam'Attitude.\n");

        return new \Illuminate\Http\UploadedFile($chemin, 'exemple.txt', 'text/plain', null, true);
    }
}
