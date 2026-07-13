<?php

// Origen: Modelo de Dominio v2, Área 2.2 "Socio". RN-01 (limite por tipo), busqueda tolerante (nombres alternativos).

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('socios', function (Blueprint $table) {
            $table->id();
            $table->string('nombre_principal');
            // Nombres alternativos: lista de texto -> jsonb en Postgres. Habilita busqueda tolerante (regla de diseno del dominio).
            $table->jsonb('nombres_alternativos')->nullable();
            $table->string('dni')->nullable();
            $table->string('email')->nullable();
            $table->string('telefono')->nullable();
            $table->date('fecha_alta');
            $table->string('estado')->default('activo'); // activo | inactivo
            $table->foreignId('tipo_socio_id')->constrained('tipos_socio');
            $table->timestamps();

            $table->index('nombre_principal');
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('socios');
    }
};
