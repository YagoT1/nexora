<?php

// Origen: Modelo de Dominio v2, Área 1.1 "Libro". D-02 (separacion Libro/Ejemplar), D-07 (ISBN no es identificador unico).

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('libros', function (Blueprint $table) {
            $table->id();
            $table->string('titulo');
            // D-07: ISBN opcional, NO unico (existen duplicados legitimos en la muestra relevada).
            $table->string('isbn')->nullable();
            $table->unsignedSmallInteger('anio_publicacion')->nullable();
            $table->string('edicion')->nullable();
            $table->string('idioma')->nullable();
            $table->text('descripcion')->nullable();
            $table->foreignId('editorial_id')->nullable()->constrained('editoriales')->nullOnDelete();
            $table->timestamps();

            $table->index('titulo');
            $table->index('isbn');
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('libros');
    }
};
