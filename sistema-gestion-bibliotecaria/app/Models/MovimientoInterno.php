<?php

// Origen: Modelo de Dominio v2, 3.5. Fuera del alcance funcional de Fase 1 (DA-07).

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class MovimientoInterno extends Model
{
    protected $table = 'movimientos_internos';

    protected $fillable = [
        'responsable_id', 'proposito', 'actividad_id', 'fecha_inicio',
        'fecha_retorno_esperada', 'fecha_retorno_efectiva', 'estado',
    ];

    public function ejemplares()
    {
        // Origen: corrección 2026-07-14 (ver ADR-012). Columna real: 'fecha_retorno_efectiva'
        // (migración 2024_01_01_000170_create_ejemplares_movimiento_interno_table).
        return $this->belongsToMany(Ejemplar::class, 'ejemplares_movimiento_interno')
            ->withPivot('fecha_retorno_efectiva');
    }
}
