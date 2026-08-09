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
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('candidature');
    }
};
