<?php

// Origen: Modelo de Dominio v2, 3.2 "Renovación". Lógica completa: Módulo 5.

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class Renovacion extends Model
{
    protected $table = 'renovaciones';

    protected $fillable = [
        'prestamo_domiciliario_id', 'fecha_renovacion', 'fecha_vencimiento_anterior',
        'nueva_fecha_vencimiento', 'registrado_por',
    ];

    public function prestamo()
    {
        return $this->belongsTo(PrestamoDomiciliario::class, 'prestamo_domiciliario_id');
    }
}
