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
        Schema::create('stage', function (Blueprint $table) {

            $table->id('idStage'); 
            $table->string('intitule', 50);
            $table->string('detail', 5000);
            $table->date('dateDebut');
            $table->date('dateFin');
        
            $table->foreignId('idEntreprise')->constrained('entreprise', 'idEntreprise');

            $table->timestamps();
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('stage');
    }
};
