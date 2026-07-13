<?php

// Origen: Modelo de Dominio v2, 4.3 "Historial de atrasos". M-02 (campo "Restriccion generada").

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('historial_atrasos', function (Blueprint $table) {
            $table->id();
            $table->foreignId('socio_id')->constrained('socios');
            $table->foreignId('prestamo_domiciliario_id')->constrained('prestamos_domiciliarios');
            $table->unsignedSmallInteger('dias_atraso');
            $table->date('fecha_devolucion_efectiva');
            $table->boolean('restriccion_generada')->default(false);
            $table->timestamps();

            $table->index('socio_id');
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('historial_atrasos');
    }
};
