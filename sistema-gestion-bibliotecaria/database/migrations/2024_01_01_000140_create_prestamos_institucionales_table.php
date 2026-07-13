<?php

// Origen: Modelo de Dominio v2, 3.4 "Préstamo institucional". Fuera del alcance funcional de Fase 1
// (DA-07: diferido a Fase 2 del software), pero la tabla se crea ahora porque el Módulo 1 exige
// las migraciones de TODAS las entidades del dominio completo.

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('prestamos_institucionales', function (Blueprint $table) {
            $table->id();
            $table->foreignId('institucion_id')->constrained('instituciones');
            $table->date('fecha_prestamo');
            $table->date('fecha_retorno_esperada');
            $table->date('fecha_retorno_efectiva')->nullable();
            $table->foreignId('autorizado_por')->constrained('users');
            $table->string('estado')->default('activo'); // activo | devuelto | atrasado
            $table->text('observaciones')->nullable();
            $table->timestamps();
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('prestamos_institucionales');
    }
};
