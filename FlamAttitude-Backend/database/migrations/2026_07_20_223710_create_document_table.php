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
        Schema::create('document', function (Blueprint $table) {
            $table->id('idDocument');
            $table->foreignId('idClient')->constrained('utilisateur', 'idUtilisateur');
            $table->foreignId('idAjoutePar')->nullable()->constrained('utilisateur', 'idUtilisateur');
            $table->string('nom', 150);
            $table->string('chemin', 255);
            $table->timestamps();
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('document');
    }
};
