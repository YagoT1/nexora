<?php

// Origen: Modelo de Dominio v2, 3.4 "Ejemplar en préstamo institucional" (entidad de asociación, C-07).

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;
use Illuminate\Support\Facades\DB;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('ejemplares_prestamo_institucional', function (Blueprint $table) {
            $table->id();
            $table->foreignId('prestamo_institucional_id')->constrained('prestamos_institucionales')->cascadeOnDelete();
            $table->foreignId('ejemplar_id')->constrained('ejemplares');
            $table->date('fecha_devolucion_efectiva')->nullable();
            $table->timestamps();

            $table->unique(['prestamo_institucional_id', 'ejemplar_id']);
        });

        // DA-09 Nivel 1 (ver nota completa en 2024_01_01_000100): un ejemplar no puede estar
        // simultaneamente en dos prestamos institucionales activos (fecha_devolucion_efectiva null = activo).
        DB::statement(
            'CREATE UNIQUE INDEX ejemplares_prestamo_inst_activo_unique
             ON ejemplares_prestamo_institucional (ejemplar_id)
             WHERE fecha_devolucion_efectiva IS NULL'
        );
    }

    public function down(): void
    {
        Schema::dropIfExists('ejemplares_prestamo_institucional');
    }
};
