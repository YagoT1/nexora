<?php

// Origen: Modelo de Dominio v2, 4.1 "Excepción autorizada". D-03 (mecanismo único), RN-10, RN-11.

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('excepciones_autorizadas', function (Blueprint $table) {
            $table->id();
            // exencion_restriccion_atraso | limite_prestamo_especial | autorizacion_salida_material_restringido
            $table->string('tipo');
            // Entidad afectada: polimorfica (Socio o Ejemplar segun el tipo).
            $table->string('entidad_afectada_type');
            $table->unsignedBigInteger('entidad_afectada_id');
            $table->foreignId('autorizado_por')->constrained('users');
            $table->date('fecha_autorizacion');
            $table->text('motivo');
            $table->date('fecha_inicio');
            $table->date('fecha_fin')->nullable(); // null = indefinida hasta revocacion explicita (RN-11)
            $table->string('estado')->default('vigente'); // vigente | vencida | revocada
            $table->foreignId('revocado_por')->nullable()->constrained('users');
            $table->date('fecha_revocacion')->nullable();
            $table->timestamps();

            $table->index(['entidad_afectada_type', 'entidad_afectada_id', 'estado']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('excepciones_autorizadas');
    }
};
