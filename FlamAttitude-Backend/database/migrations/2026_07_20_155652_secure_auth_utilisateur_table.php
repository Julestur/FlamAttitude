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
        Schema::table('utilisateur', function (Blueprint $table) {
            // Mot de passe temporaire en clair : fonctionnalité morte et dangereuse, supprimée.
            $table->dropColumn('mdp_tmp');

            // Double authentification par code email
            $table->string('two_factor_code')->nullable()->after('codeVerif');
            $table->timestamp('two_factor_expires_at')->nullable()->after('two_factor_code');
            $table->unsignedTinyInteger('two_factor_attempts')->default(0)->after('two_factor_expires_at');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('utilisateur', function (Blueprint $table) {
            $table->string('mdp_tmp')->default('vide');
            $table->dropColumn(['two_factor_code', 'two_factor_expires_at', 'two_factor_attempts']);
        });
    }
};
