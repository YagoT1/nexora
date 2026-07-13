<?php

// Origen: Modelo de Dominio v2, 4.2 "Restricción de socio". RN-06, RN-07, RN-18.

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('restricciones_socio', function (Blueprint $table) {
            $table->id();
            $table->foreignId('socio_id')->constrained('socios');
            $table->string('tipo'); // automatica | manual
            $table->date('fecha_inicio');
            $table->date('fecha_fin');
            $table->unsignedSmallInteger('dias_atraso_origen')->nullable();
            $table->foreignId('prestamo_domiciliario_id')->nullable()->constrained('prestamos_domiciliarios')->nullOnDelete();
            // "Generada por": sistema o usuario. Nullable = generada por el sistema (tarea programada / Modulo 4).
            $table->foreignId('generada_por_usuario_id')->nullable()->constrained('users');
            $table->text('observaciones')->nullable();
            $table->timestamps();

            $table->index(['socio_id', 'fecha_fin']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('restricciones_socio');
    }
};
