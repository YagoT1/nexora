<?php

// Origen: Modelo de Dominio v2, 3.1 "Préstamo domiciliario". RN-02, RN-04, RN-12, RN-13, RN-19, C-01, DA-09.

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;
use Illuminate\Support\Facades\DB;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('prestamos_domiciliarios', function (Blueprint $table) {
            $table->id();
            $table->foreignId('ejemplar_id')->constrained('ejemplares');
            $table->foreignId('socio_id')->constrained('socios');
            $table->timestamp('fecha_registro');
            $table->date('fecha_prestamo');
            // fecha_vencimiento SIEMPRE refleja el vencimiento vigente (se actualiza en cada renovacion). RN-19.
            $table->date('fecha_vencimiento');
            $table->date('fecha_devolucion_efectiva')->nullable();
            // C-01: sin estado "renovado". Solo activo | devuelto | atrasado.
            $table->string('estado')->default('activo');
            $table->foreignId('registrado_por')->constrained('users');
            $table->boolean('es_excepcion_de_limite')->default(false);
            $table->text('motivo_excepcion_limite')->nullable();
            $table->timestamps();

            $table->index(['ejemplar_id', 'estado']);
            $table->index(['socio_id', 'estado']);
            $table->index('fecha_vencimiento');
        });

        // DA-09 Nivel 1: indice unico parcial - un ejemplar no puede tener mas de un prestamo domiciliario activo.
        // Enforcement de RN-04 a nivel de motor. La verificacion CRUZADA entre los 4 tipos de movimiento
        // (domiciliario / institucional / interno / custodia) es responsabilidad de la capa de aplicacion
        // (ver App\Models\Ejemplar::tieneMovimientoActivo()), porque un indice unico no puede abarcar
        // varias tablas. Este es un tradeoff ya aceptado en la Propuesta de Arquitectura v2 (DA-09).
        DB::statement(
            'CREATE UNIQUE INDEX prestamos_domiciliarios_ejemplar_activo_unique
             ON prestamos_domiciliarios (ejemplar_id)
             WHERE estado IN (\'activo\', \'atrasado\')'
        );
    }

    public function down(): void
    {
        Schema::dropIfExists('prestamos_domiciliarios');
    }
};
