<?php

// Origen: Modelo de Dominio v2, Área 1.5 "Ejemplar". D-09 (estado parcialmente derivado, C-03).
// Los estados derivados (Prestado / En movimiento interno / En custodia externa) NO se almacenan aqui:
// se calculan consultando las tablas de movimiento (ver App\Models\Ejemplar::estadoActual()).
// Solo se almacena el estado MANUAL cuando esta activo (En reparacion / Extraviado).

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('ejemplares', function (Blueprint $table) {
            $table->id();
            $table->foreignId('libro_id')->constrained('libros');
            // Estado manual: null | en_reparacion | extraviado. Null = sin estado manual activo (D-09).
            $table->string('estado_manual')->nullable();
            // Modalidad de acceso: propiedad permanente, independiente del estado operativo.
            $table->string('modalidad_acceso')->default('libre_circulacion');
            // libre_circulacion | solo_sala | restringido_a_autorizacion
            $table->text('condicion_fisica')->nullable();
            $table->date('fecha_ingreso');
            $table->string('origen')->default('compra'); // compra | donacion | otro
            $table->timestamps();

            $table->index('modalidad_acceso');
            $table->index('estado_manual');
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('ejemplares');
    }
};
