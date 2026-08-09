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
            $table->boolean('rappel_semaine_envoye')->default(false)->after('motif');
            $table->boolean('rappel_veille_envoye')->default(false)->after('rappel_semaine_envoye');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('rdv', function (Blueprint $table) {
            $table->dropColumn(['rappel_semaine_envoye', 'rappel_veille_envoye']);
        });
    }
};
