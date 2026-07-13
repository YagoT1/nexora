<?php

// Origen: Modelo de Dominio v2, 2.1 "Tipo de Socio". D-04.

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class TipoSocio extends Model
{
    protected $table = 'tipos_socio';

    protected $fillable = [
        'nombre', 'limite_prestamos_simultaneos', 'sujeto_a_restriccion_automatica',
    ];

    protected function casts(): array
    {
        return ['sujeto_a_restriccion_automatica' => 'boolean'];
    }

    public function socios()
    {
        return $this->hasMany(Socio::class);
    }
}
