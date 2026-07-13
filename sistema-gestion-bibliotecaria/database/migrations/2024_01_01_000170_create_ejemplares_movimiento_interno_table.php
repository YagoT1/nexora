<?php

// Origen: Modelo de Dominio v2, 3.5 "Ejemplar en movimiento interno" (entidad de asociación, C-07).

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;
use Illuminate\Support\Facades\DB;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('ejemplares_movimiento_interno', function (Blueprint $table) {
            $table->id();
            $table->foreignId('movimiento_interno_id')->constrained('movimientos_internos')->cascadeOnDelete();
            $table->foreignId('ejemplar_id')->constrained('ejemplares');
            $table->date('fecha_retorno_efectiva')->nullable();
            $table->timestamps();

            $table->unique(['movimiento_interno_id', 'ejemplar_id']);
        });

        // DA-09 Nivel 1 (ver nota completa en 2024_01_01_000100).
        DB::statement(
            'CREATE UNIQUE INDEX ejemplares_mov_interno_activo_unique
             ON ejemplares_movimiento_interno (ejemplar_id)
             WHERE fecha_retorno_efectiva IS NULL'
        );
    }

    public function down(): void
    {
        Schema::dropIfExists('ejemplares_movimiento_interno');
    }
};
