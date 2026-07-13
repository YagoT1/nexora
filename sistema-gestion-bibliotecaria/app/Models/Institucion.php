<?php

// Origen: Modelo de Dominio v2, 5.1 "Institución".

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class Institucion extends Model
{
    protected $table = 'instituciones';
    protected $fillable = ['nombre', 'tipo', 'persona_contacto', 'telefono', 'email'];
}
