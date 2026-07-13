<?php

// Origen: Modelo de Dominio v2, 3.3 "Reserva". Lógica completa: Módulo 5.

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class Reserva extends Model
{
    protected $table = 'reservas';

    protected $fillable = [
        'libro_id', 'socio_id', 'fecha_reserva', 'estado',
        'fecha_alerta_al_personal', 'fecha_limite_retiro', 'ejemplar_asignado_id',
    ];

    protected function casts(): array
    {
        return [
            'fecha_reserva' => 'date',
            'fecha_alerta_al_personal' => 'datetime',
            'fecha_limite_retiro' => 'datetime',
        ];
    }

    public function libro()
    {
        return $this->belongsTo(Libro::class);
    }

    public function socio()
    {
        return $this->belongsTo(Socio::class);
    }

    public function ejemplarAsignado()
    {
        return $this->belongsTo(Ejemplar::class, 'ejemplar_asignado_id');
    }
}
