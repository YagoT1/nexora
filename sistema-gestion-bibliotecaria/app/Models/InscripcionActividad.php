<?php

// Origen: Modelo de Dominio v2, 5.3.

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class InscripcionActividad extends Model
{
    protected $table = 'inscripciones_actividad';

    protected $fillable = [
        'actividad_id', 'tipo_inscripto', 'socio_id', 'nombre_externo', 'telefono_externo',
        'institucion_id', 'cantidad_participantes', 'asistencia_efectiva', 'fecha_inscripcion',
    ];

    protected function casts(): array
    {
        return [
            'asistencia_efectiva' => 'boolean',
            'fecha_inscripcion' => 'date',
        ];
    }

    public function actividad()
    {
        return $this->belongsTo(Actividad::class);
    }
}
