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
        Schema::table('rdv', function (Blueprint $table) {
            // Empêche deux RDV pour le même membre au même horaire (ex: double clic sur "Confirmer").
            $table->unique(['idMembre', 'date', 'heure_debut'], 'rdv_creneau_unique');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('rdv', function (Blueprint $table) {
            $table->dropUnique('rdv_creneau_unique');
        });
    }
};
