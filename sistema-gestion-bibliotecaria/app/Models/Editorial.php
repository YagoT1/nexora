<?php

// Origen: Modelo de Dominio v2, 1.3 "Editorial".

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class Editorial extends Model
{
    protected $table = 'editoriales';
    protected $fillable = ['nombre'];

    public function libros()
    {
        return $this->hasMany(Libro::class);
    }
}
