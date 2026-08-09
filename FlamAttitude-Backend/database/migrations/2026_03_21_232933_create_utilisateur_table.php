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
        Schema::create('utilisateur', function (Blueprint $table) {

            $table->id('idUtilisateur'); 
            $table->string('nom', 50);
            $table->string('prenom', 50);
            $table->string('email', 100)->unique();
            $table->string('identifiant', 50)->unique();
            $table->string('mdp');
            $table->string('pdp', 100);
            $table->string('mdp_tmp');
        
            $table->foreignId('idStatut')->constrained('statut', 'idStatut');
            $table->foreignId('idEntreprise')->nullable()->constrained('entreprise', 'idEntreprise');
            $table->foreignId('idClasse')->nullable()->constrained('classe', 'idClasse');
        
            $table->integer('estVerif')->default(0);
            $table->string('codeVerif')->nullable();
            $table->timestamps();
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('utilisateur');
    }
};

