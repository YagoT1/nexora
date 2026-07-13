<?php

// Origen: Modelo de Dominio v2, 3.5 "Movimiento interno". Diferido a Fase 2 del software (DA-07).

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('movimientos_internos', function (Blueprint $table) {
            $table->id();
            $table->foreignId('responsable_id')->constrained('users');
            $table->text('proposito');
            $table->foreignId('actividad_id')->nullable(); // FK diferida: se agrega al crear 'actividades'
            $table->date('fecha_inicio');
            $table->date('fecha_retorno_esperada');
            $table->date('fecha_retorno_efectiva')->nullable();
            $table->string('estado')->default('activo'); // activo | finalizado
            $table->timestamps();
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('movimientos_internos');
    }
};
