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
    Schema::create('message', function (Blueprint $table) {
        $table->id('idMessage');
        $table->foreignId('idCandidature')->constrained('candidature', 'idCandidature')->onDelete('cascade');
        $table->foreignId('idExpediteur')->constrained('utilisateur', 'idUtilisateur');
        $table->enum('canal', ['etudiant_entreprise', 'etudiant_profs', 'entreprise_profs', 'admin']);
        $table->text('contenu');
        $table->string('fichier', 255)->nullable();
        $table->string('nom_fichier', 255)->nullable();
        $table->timestamps();
    });
}

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('message');
    }
};
