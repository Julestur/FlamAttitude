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
        Schema::create('membre_type_rdv', function (Blueprint $table) {
            $table->foreignId('idMembre')->constrained('utilisateur', 'idUtilisateur')->cascadeOnDelete();
            $table->foreignId('idTypeRdv')->constrained('type_rdv', 'idTypeRdv')->cascadeOnDelete();
            $table->primary(['idMembre', 'idTypeRdv']);
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('membre_type_rdv');
    }
};
