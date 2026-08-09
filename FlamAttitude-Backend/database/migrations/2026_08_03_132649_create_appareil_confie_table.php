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
        Schema::create('appareil_confie', function (Blueprint $table) {
            $table->id('idAppareil');
            $table->foreignId('idUtilisateur')->constrained('utilisateur', 'idUtilisateur')->cascadeOnDelete();
            $table->string('selecteur', 40)->unique();
            $table->string('validateur_hache');
            $table->timestamp('expire_le');
            $table->timestamps();
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('appareil_confie');
    }
};
