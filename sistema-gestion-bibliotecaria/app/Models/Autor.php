<?php

// Origen: Modelo de Dominio v2, 1.2 "Autor".

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class Autor extends Model
{
    protected $table = 'autores';
    protected $fillable = ['nombre', 'notas'];

    public function libros()
    {
        return $this->belongsToMany(Libro::class, 'libro_autor');
    }
}
