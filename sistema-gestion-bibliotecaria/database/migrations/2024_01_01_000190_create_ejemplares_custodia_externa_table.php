<?php

// Origen: Modelo de Dominio v2, 3.6 "Ejemplar en custodia externa" (entidad de asociación, C-07).

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;
use Illuminate\Support\Facades\DB;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('ejemplares_custodia_externa', function (Blueprint $table) {
            $table->id();
            $table->foreignId('custodia_externa_id')->constrained('custodias_externas')->cascadeOnDelete();
            $table->foreignId('ejemplar_id')->constrained('ejemplares');
            $table->date('fecha_retorno_efectiva')->nullable();
            $table->timestamps();

            $table->unique(['custodia_externa_id', 'ejemplar_id']);
        });

        // DA-09 Nivel 1 (ver nota completa en 2024_01_01_000100).
        DB::statement(
            'CREATE UNIQUE INDEX ejemplares_custodia_activa_unique
             ON ejemplares_custodia_externa (ejemplar_id)
             WHERE fecha_retorno_efectiva IS NULL'
        );
    }

    public function down(): void
    {
        Schema::dropIfExists('ejemplares_custodia_externa');
    }
};
