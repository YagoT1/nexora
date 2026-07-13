<?php

// Origen: Modelo de Dominio v2, 3.4. Fuera del alcance funcional de Fase 1 (DA-07); estructura
// creada en Módulo 1 por completitud del esquema completo.

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class PrestamoInstitucional extends Model
{
    protected $table = 'prestamos_institucionales';

    protected $fillable = [
        'institucion_id', 'fecha_prestamo', 'fecha_retorno_esperada',
        'fecha_retorno_efectiva', 'autorizado_por', 'estado', 'observaciones',
    ];

    public function institucion()
    {
        return $this->belongsTo(Institucion::class);
    }

    public function ejemplares()
    {
        return $this->belongsToMany(Ejemplar::class, 'ejemplares_prestamo_institucional')
            ->withPivot('fecha_devolucion_efectiva');
    }
}
