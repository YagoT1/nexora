<?php

// Origen: Modelo de Dominio v2, 5.6. RN-16, D-10 (vínculo unidireccional hacia Ejemplar).

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class ItemDonacion extends Model
{
    protected $table = 'items_donacion';

    protected $fillable = [
        'donacion_id', 'tipo_material', 'descripcion', 'estado_evaluacion',
        'motivo_descarte', 'ejemplar_generado_id',
    ];

    public function donacion()
    {
        return $this->belongsTo(Donacion::class, 'donacion_id');
    }

    // D-10: navegación unidireccional. No existe Ejemplar::itemDonacionOrigen() a propósito.
    public function ejemplarGenerado()
    {
        return $this->belongsTo(Ejemplar::class, 'ejemplar_generado_id');
    }
}
