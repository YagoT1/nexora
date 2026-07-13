<?php

// Origen: Modelo de Dominio v2, Área 5.6 "Ítem de donación". RN-16, D-10 (vínculo unidireccional).

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('items_donacion', function (Blueprint $table) {
            $table->id();
            $table->foreignId('donacion_id')->constrained('donaciones');
            $table->string('tipo_material'); // bibliografico | no_bibliografico
            $table->text('descripcion');
            $table->string('estado_evaluacion')->default('pendiente'); // pendiente | aceptado | descartado
            $table->text('motivo_descarte')->nullable();
            // D-10: vinculo unidireccional Item de donacion -> Ejemplar. No existe FK inversa en 'ejemplares'.
            $table->foreignId('ejemplar_generado_id')->nullable()->constrained('ejemplares')->nullOnDelete();
            $table->timestamps();
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('items_donacion');
    }
};
