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
        Schema::create('facture', function (Blueprint $table) {
            $table->id('idFacture');
            $table->foreignId('idClient')->constrained('utilisateur', 'idUtilisateur');
            $table->foreignId('idCreePar')->nullable()->constrained('utilisateur', 'idUtilisateur');
            $table->decimal('montant', 8, 2);
            $table->string('description', 500);
            $table->enum('statut', ['en_attente', 'payee'])->default('en_attente');
            $table->date('date_emission');
            $table->string('chemin', 255)->nullable();
            $table->timestamps();
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('facture');
    }
};
