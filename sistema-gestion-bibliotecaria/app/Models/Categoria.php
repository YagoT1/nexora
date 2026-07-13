<?php

// Origen: Modelo de Dominio v2, 1.4 "Categoría". D-06 / CL-02: jerarquía máxima de 2 niveles.

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class Categoria extends Model
{
    protected $table = 'categorias';
    protected $fillable = ['nombre', 'categoria_padre_id'];

    public function padre()
    {
        return $this->belongsTo(Categoria::class, 'categoria_padre_id');
    }

    public function subcategorias()
    {
        return $this->hasMany(Categoria::class, 'categoria_padre_id');
    }

    /**
     * CL-02 / criterio de aceptación Módulo 2: "El sistema no permite crear una subcategoría
     * cuyo padre ya es subcategoría (profundidad máxima 2)". Se deja implementado aquí porque
     * es una invariante del modelo, aunque su UI de creación pertenece al Módulo 2 (Catálogo).
     */
    public function puedeSerPadre(): bool
    {
        return $this->categoria_padre_id === null;
    }

    public function libros()
    {
        return $this->belongsToMany(Libro::class, 'libro_categoria');
    }
}
