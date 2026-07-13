<?php

// Origen: Modelo de Dominio v2, Área 5.5 "Donación".

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('donaciones', function (Blueprint $table) {
            $table->id();
            $table->foreignId('donante_id')->constrained('donantes');
            $table->date('fecha');
            $table->boolean('reconocimiento_institucional')->default(false);
            $table->text('condicion_del_donante')->nullable();
            $table->text('observaciones')->nullable();
            $table->timestamps();
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('donaciones');
    }
};
