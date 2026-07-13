<?php

// Origen: Modelo de Dominio v2, 1.1 "Autores | Relación | Uno o más autores" (M:N).

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('libro_autor', function (Blueprint $table) {
            $table->foreignId('libro_id')->constrained('libros')->cascadeOnDelete();
            $table->foreignId('autor_id')->constrained('autores')->cascadeOnDelete();
            $table->primary(['libro_id', 'autor_id']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('libro_autor');
    }
};
