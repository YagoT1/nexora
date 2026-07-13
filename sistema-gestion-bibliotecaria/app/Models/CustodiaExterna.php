<?php

// Origen: Modelo de Dominio v2, 3.6. RN-17. Fuera del alcance funcional de Fase 1 (DA-07).

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class CustodiaExterna extends Model
{
    protected $table = 'custodias_externas';

    protected $fillable = [
        'institucion_o_evento_custodio', 'persona_contacto', 'actividad_id',
        'fecha_salida', 'fecha_retorno_esperada', 'fecha_retorno_efectiva',
        'estado', 'observaciones',
    ];

    public function ejemplares()
    {
        return $this->belongsToMany(Ejemplar::class, 'ejemplares_custodia_externa')
            ->withPivot('fecha_devolucion_efectiva');
    }
}
