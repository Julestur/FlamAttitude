<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * Run the migrations.
     */
    public function up(): void
    {
        // Ordre imposé par les clés étrangères : message -> candidature -> stage,
        // puis les colonnes de utilisateur, puis classe/entreprise.
        Schema::dropIfExists('message');
        Schema::dropIfExists('candidature');
        Schema::dropIfExists('stage');

        Schema::table('utilisateur', function (Blueprint $table) {
            $table->dropForeign(['idClasse']);
            $table->dropForeign(['idEntreprise']);
            $table->dropColumn(['idClasse', 'idEntreprise']);
        });

        Schema::dropIfExists('classe');
        Schema::dropIfExists('entreprise');
    }

    /**
     * Reverse the migrations.
     *
     * Recrée uniquement la structure (les données de l'ancien domaine ne sont pas récupérables).
     */
    public function down(): void
    {
        Schema::create('classe', function (Blueprint $table) {
            $table->id('idClasse');
            $table->string('nom', 50)->unique();
            $table->timestamps();
        });

        Schema::create('entreprise', function (Blueprint $table) {
            $table->id('idEntreprise');
            $table->string('nom', 50)->unique();
            $table->timestamps();
        });

        Schema::table('utilisateur', function (Blueprint $table) {
            $table->foreignId('idEntreprise')->nullable()->after('idStatut')->constrained('entreprise', 'idEntreprise');
            $table->foreignId('idClasse')->nullable()->after('idEntreprise')->constrained('classe', 'idClasse');
        });

        Schema::create('stage', function (Blueprint $table) {
            $table->id('idStage');
            $table->string('intitule', 50);
            $table->string('detail', 5000);
            $table->date('dateDebut');
            $table->date('dateFin');
            $table->foreignId('idEntreprise')->constrained('entreprise', 'idEntreprise');
            $table->timestamps();
        });

        Schema::create('candidature', function (Blueprint $table) {
            $table->id('idCandidature');
            $table->integer('statut')->default(0);
            $table->tinyInteger('statut_entreprise')->default(0);
            $table->tinyInteger('statut_prof')->default(0);
            $table->string('CV', 100)->nullable();
            $table->integer('estVerif_CV')->default(0);
            $table->string('LettreMotivation', 100)->nullable();
            $table->integer('estVerif_LettreMotivation')->default(0);
            $table->string('Convention', 255)->nullable();
            $table->tinyInteger('estVerif_Convention_Entreprise')->default(0);
            $table->tinyInteger('estVerif_Convention_Prof')->default(0);
            $table->string('Remarque_Entreprise', 1000)->nullable();
            $table->string('Remarque_Prof', 1000)->nullable();
            $table->foreignId('idStage')->constrained('stage', 'idStage');
            $table->foreignId('idEntreprise')->nullable()->constrained('entreprise', 'idEntreprise');
            $table->foreignId('idUtilisateur')->nullable()->constrained('utilisateur', 'idUtilisateur');
            $table->foreignId('idProf')->nullable()->constrained('utilisateur', 'idUtilisateur');
            $table->timestamps();
        });

        Schema::create('message', function (Blueprint $table) {
            $table->id('idMessage');
            $table->foreignId('idCandidature')->constrained('candidature', 'idCandidature')->onDelete('cascade');
            $table->foreignId('idExpediteur')->constrained('utilisateur', 'idUtilisateur');
            $table->enum('canal', ['etudiant_entreprise', 'etudiant_profs', 'entreprise_profs', 'admin']);
            $table->text('contenu');
            $table->string('fichier', 255)->nullable();
            $table->string('nom_fichier', 255)->nullable();
            $table->timestamps();
        });
    }
};
