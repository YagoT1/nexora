<?php

// Origen: Modelo de Dominio v2, 1.1 "Categorías | Relación | Una o más categorías" (M:N).

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('libro_categoria', function (Blueprint $table) {
            $table->foreignId('libro_id')->constrained('libros')->cascadeOnDelete();
            $table->foreignId('categoria_id')->constrained('categorias')->cascadeOnDelete();
            $table->primary(['libro_id', 'categoria_id']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('libro_categoria');
    }
};
