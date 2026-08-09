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
        Schema::create('creneau', function (Blueprint $table) {
            $table->id('idCreneau');
            $table->foreignId('idMembre')->constrained('utilisateur', 'idUtilisateur');
            $table->date('date');
            $table->time('heure_debut');
            $table->time('heure_fin');
            $table->foreignId('idClient')->nullable()->constrained('utilisateur', 'idUtilisateur');
            $table->string('motif', 500)->nullable();
            $table->timestamps();
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('creneau');
    }
};
