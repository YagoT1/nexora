<?php

// Origen: Modelo de Dominio v2, 3.6 "Custodia externa". RN-17. Diferido a Fase 2 del software (DA-07).

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('custodias_externas', function (Blueprint $table) {
            $table->id();
            $table->string('institucion_o_evento_custodio');
            $table->string('persona_contacto')->nullable();
            $table->foreignId('actividad_id')->nullable(); // FK diferida: se agrega al crear 'actividades'
            $table->date('fecha_salida');
            $table->date('fecha_retorno_esperada');
            $table->date('fecha_retorno_efectiva')->nullable();
            $table->string('estado')->default('activa'); // activa | finalizada | atrasada
            $table->text('observaciones')->nullable();
            $table->timestamps();
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('custodias_externas');
    }
};
