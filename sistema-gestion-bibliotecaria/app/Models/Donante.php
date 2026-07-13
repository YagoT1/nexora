<?php

// Origen: Modelo de Dominio v2, 5.4.

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class Donante extends Model
{
    protected $table = 'donantes';
    protected $fillable = ['tipo', 'nombre', 'contacto'];

    public function donaciones()
    {
        return $this->hasMany(Donacion::class);
    }
}
