<?php

// Origen: Modelo de Dominio v2, 4.2. RN-06, RN-07, RN-18.

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class RestriccionSocio extends Model
{
    protected $table = 'restricciones_socio';

    protected $fillable = [
        'socio_id', 'tipo', 'fecha_inicio', 'fecha_fin', 'dias_atraso_origen',
        'prestamo_domiciliario_id', 'generada_por_usuario_id', 'observaciones',
    ];

    protected function casts(): array
    {
        return ['fecha_inicio' => 'date', 'fecha_fin' => 'date'];
    }

    public function socio()
    {
        return $this->belongsTo(Socio::class);
    }

    public function estaActiva(): bool
    {
        return $this->fecha_fin->isFuture() || $this->fecha_fin->isToday();
    }
}
