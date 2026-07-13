<?php

// Origen: Modelo de Dominio v2, 3.2 "Renovación". RN-03, RN-19.

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('renovaciones', function (Blueprint $table) {
            $table->id();
            $table->foreignId('prestamo_domiciliario_id')->constrained('prestamos_domiciliarios');
            $table->date('fecha_renovacion');
            $table->date('fecha_vencimiento_anterior');
            $table->date('nueva_fecha_vencimiento');
            $table->foreignId('registrado_por')->constrained('users');
            $table->timestamps();
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('renovaciones');
    }
};
