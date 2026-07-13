<?php

// Origen: Modelo de Dominio v2, 5.2. RN-15. Fuera del alcance funcional de Fase 1 (DA-07).

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class Actividad extends Model
{
    protected $table = 'actividades';

    protected $fillable = [
        'titulo', 'tipo', 'modalidad_participacion', 'fecha_inicio', 'fecha_fin',
        'descripcion', 'es_gratuita', 'monto_referencia', 'cupo_maximo',
        'institucion_co_organizadora_id',
    ];

    protected function casts(): array
    {
        return [
            'fecha_inicio' => 'date',
            'fecha_fin' => 'date',
            'es_gratuita' => 'boolean',
        ];
    }

    public function institucionCoOrganizadora()
    {
        return $this->belongsTo(Institucion::class, 'institucion_co_organizadora_id');
    }

    public function inscripciones()
    {
        return $this->hasMany(InscripcionActividad::class);
    }
}
