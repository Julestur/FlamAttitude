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
        Schema::create('type_rdv', function (Blueprint $table) {
            $table->id('idTypeRdv');
            $table->string('nom', 100);
            $table->unsignedSmallInteger('duree_minutes');
            $table->string('couleur', 7)->default('#5988DA');
            $table->boolean('actif')->default(true);
            $table->timestamps();
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('type_rdv');
    }
};
