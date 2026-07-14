<?php

// Origen: Modelo de Dominio v2, 2.2 "Socio". RN-01, RN-07. Auditado (Comisión Directiva trata
// altas/bajas de socios como dato sensible — DA-05 protección de datos, Ley 25.326).

namespace App\Models;

use App\Support\Auditing\Auditable;
use Illuminate\Database\Eloquent\Model;

class Socio extends Model
{
    use Auditable;

    protected $table = 'socios';

    protected $fillable = [
        'nombre_principal', 'nombres_alternativos', 'dni', 'email', 'telefono',
        'fecha_alta', 'estado', 'tipo_socio_id',
    ];

    protected function casts(): array
    {
        return [
            'nombres_alternativos' => 'array',
            'fecha_alta' => 'date',
        ];
    }

    public function tipoSocio()
    {
        return $this->belongsTo(TipoSocio::class);
    }

    public function prestamosDomiciliarios()
    {
        return $this->hasMany(PrestamoDomiciliario::class);
    }

    public function restricciones()
    {
        return $this->hasMany(RestriccionSocio::class);
    }

    public function historialAtrasos()
    {
        return $this->hasMany(HistorialAtraso::class);
    }

    // Origen: Módulo 3 — Socios, Paso 2. Inversa de Reserva::socio(), que ya existía desde Módulo 1.
    // Necesaria para la vista de mostrador (Paso 5): reservas activas del socio.
    public function reservas()
    {
        return $this->hasMany(Reserva::class);
    }
}
