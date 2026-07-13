<?php

// Origen: Modelo de Dominio v2, Área 5.3 "Inscripción a actividad".

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('inscripciones_actividad', function (Blueprint $table) {
            $table->id();
            $table->foreignId('actividad_id')->constrained('actividades');
            $table->string('tipo_inscripto'); // socio | persona_externa | institucion
            $table->foreignId('socio_id')->nullable()->constrained('socios')->nullOnDelete();
            $table->string('nombre_externo')->nullable();
            $table->string('telefono_externo')->nullable();
            $table->foreignId('institucion_id')->nullable()->constrained('instituciones')->nullOnDelete();
            $table->unsignedInteger('cantidad_participantes')->default(1);
            $table->boolean('asistencia_efectiva')->nullable();
            $table->date('fecha_inscripcion');
            $table->timestamps();
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('inscripciones_actividad');
    }
};
