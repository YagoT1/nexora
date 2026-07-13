<?php

// Origen: Modelo de Dominio v2, 4.3.

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class HistorialAtraso extends Model
{
    protected $table = 'historial_atrasos';

    protected $fillable = [
        'socio_id', 'prestamo_domiciliario_id', 'dias_atraso',
        'fecha_devolucion_efectiva', 'restriccion_generada',
    ];

    protected function casts(): array
    {
        return [
            'fecha_devolucion_efectiva' => 'date',
            'restriccion_generada' => 'boolean',
        ];
    }

    public function socio()
    {
        return $this->belongsTo(Socio::class);
    }
}
