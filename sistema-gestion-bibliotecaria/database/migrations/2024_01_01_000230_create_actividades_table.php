<?php

// Origen: Modelo de Dominio v2, Área 5.2 "Actividad". RN-15. CL-03 (fecha_fin obligatoria).
// Diferido a Fase 2 del software (DA-07), tabla creada ahora por alcance del Módulo 1.

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('actividades', function (Blueprint $table) {
            $table->id();
            $table->string('titulo');
            $table->string('tipo'); // charla | taller | presentacion_de_libro | cine_debate | exposicion | visita_escolar | feria | otro
            $table->string('modalidad_participacion'); // abierta | con_inscripcion
            $table->date('fecha_inicio');
            $table->date('fecha_fin'); // CL-03: obligatorio, igual a fecha_inicio si es de un solo dia.
            $table->text('descripcion')->nullable();
            $table->boolean('es_gratuita')->default(true);
            $table->decimal('monto_referencia', 10, 2)->nullable(); // RN-15: informativo, gestion economica fuera del sistema.
            $table->unsignedInteger('cupo_maximo')->nullable();
            $table->foreignId('institucion_co_organizadora_id')->nullable()->constrained('instituciones')->nullOnDelete();
            $table->timestamps();
        });

        // Ahora que 'actividades' existe, se completan las FK diferidas (ver 000160 y 000180).
        Schema::table('movimientos_internos', function (Blueprint $table) {
            $table->foreign('actividad_id')->references('id')->on('actividades')->nullOnDelete();
        });
        Schema::table('custodias_externas', function (Blueprint $table) {
            $table->foreign('actividad_id')->references('id')->on('actividades')->nullOnDelete();
        });
    }

    public function down(): void
    {
        Schema::table('movimientos_internos', function (Blueprint $table) {
            $table->dropForeign(['actividad_id']);
        });
        Schema::table('custodias_externas', function (Blueprint $table) {
            $table->dropForeign(['actividad_id']);
        });
        Schema::dropIfExists('actividades');
    }
};
