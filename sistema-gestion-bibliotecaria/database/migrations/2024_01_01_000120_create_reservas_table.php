<?php

// Origen: Modelo de Dominio v2, 3.3 "Reserva". RN-05, RN-20, C-06.

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('reservas', function (Blueprint $table) {
            $table->id();
            $table->foreignId('libro_id')->constrained('libros');
            $table->foreignId('socio_id')->constrained('socios');
            $table->date('fecha_reserva');
            // pendiente | personal_alertado | retirada | vencida_por_no_retiro | cancelada
            $table->string('estado')->default('pendiente');
            $table->timestamp('fecha_alerta_al_personal')->nullable();
            $table->timestamp('fecha_limite_retiro')->nullable();
            $table->foreignId('ejemplar_asignado_id')->nullable()->constrained('ejemplares')->nullOnDelete();
            $table->timestamps();

            $table->index(['libro_id', 'estado']);
            $table->index(['socio_id', 'libro_id']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('reservas');
    }
};
