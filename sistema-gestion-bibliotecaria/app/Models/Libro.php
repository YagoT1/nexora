<?php

// Origen: Modelo de Dominio v2, 1.1 "Libro".

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class Libro extends Model
{
    protected $table = 'libros';

    protected $fillable = [
        'titulo', 'isbn', 'anio_publicacion', 'edicion', 'idioma', 'descripcion', 'editorial_id',
    ];

    public function editorial()
    {
        return $this->belongsTo(Editorial::class);
    }

    public function autores()
    {
        return $this->belongsToMany(Autor::class, 'libro_autor');
    }

    public function categorias()
    {
        return $this->belongsToMany(Categoria::class, 'libro_categoria');
    }

    public function ejemplares()
    {
        return $this->hasMany(Ejemplar::class);
    }
}
