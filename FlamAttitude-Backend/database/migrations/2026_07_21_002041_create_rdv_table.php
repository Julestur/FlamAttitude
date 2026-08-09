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
        Schema::create('rdv', function (Blueprint $table) {
            $table->id('idRdv');
            $table->foreignId('idMembre')->constrained('utilisateur', 'idUtilisateur');
            $table->foreignId('idClient')->constrained('utilisateur', 'idUtilisateur');
            $table->foreignId('idTypeRdv')->constrained('type_rdv', 'idTypeRdv');
            $table->date('date');
            $table->time('heure_debut');
            $table->time('heure_fin');
            $table->string('motif', 500)->nullable();
            $table->timestamps();
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('rdv');
    }
};
