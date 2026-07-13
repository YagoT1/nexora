<?php

// Origen: Modelo de Dominio v2, 4.1. D-03, RN-10, RN-11. Auditado (RN-11: trazabilidad completa).

namespace App\Models;

use App\Support\Auditing\Auditable;
use Illuminate\Database\Eloquent\Model;

class ExcepcionAutorizada extends Model
{
    use Auditable;

    protected $table = 'excepciones_autorizadas';

    public const TIPO_EXENCION_RESTRICCION = 'exencion_restriccion_atraso';
    public const TIPO_LIMITE_ESPECIAL = 'limite_prestamo_especial';
    public const TIPO_AUTORIZACION_MATERIAL_RESTRINGIDO = 'autorizacion_salida_material_restringido';

    protected $fillable = [
        'tipo', 'entidad_afectada_type', 'entidad_afectada_id', 'autorizado_por',
        'fecha_autorizacion', 'motivo', 'fecha_inicio', 'fecha_fin', 'estado',
        'revocado_por', 'fecha_revocacion',
    ];

    protected function casts(): array
    {
        return [
            'fecha_autorizacion' => 'date',
            'fecha_inicio' => 'date',
            'fecha_fin' => 'date',
            'fecha_revocacion' => 'date',
        ];
    }

    public function entidadAfectada()
    {
        return $this->morphTo(__FUNCTION__, 'entidad_afectada_type', 'entidad_afectada_id');
    }

    public function autorizadoPor()
    {
        return $this->belongsTo(User::class, 'autorizado_por');
    }

    public function estaVigente(): bool
    {
        if ($this->estado !== 'vigente') {
            return false;
        }

        return $this->fecha_fin === null || $this->fecha_fin->isFuture() || $this->fecha_fin->isToday();
    }
}
