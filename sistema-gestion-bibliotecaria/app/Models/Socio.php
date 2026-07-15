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

    // Origen: Módulo 4 — Préstamos, Paso 1. Extrae a un método de dominio reutilizable el conteo de
    // préstamos abiertos que ya se repetía inline en SocioController::show() (Módulo 3) y que RN-01
    // necesita para verificar el límite del Tipo de Socio antes de registrar un nuevo préstamo.
    public function cantidadPrestamosActivos(): int
    {
        return $this->prestamosDomiciliarios()
            ->whereIn('estado', PrestamoDomiciliario::ESTADOS_ABIERTOS)
            ->count();
    }

    /**
     * Origen: Módulo 3 — Socios, Paso 4 (búsqueda tolerante a mayúsculas/minúsculas y acentos,
     * R-1/R-3 de BRIEFING-MODULO-3-SOCIOS.md). Extraído desde SocioController::index() a un scope
     * reutilizable en Módulo 4 (selección de socio al registrar un préstamo), evitando duplicar la
     * comparación unaccent()/jsonb_array_elements_text en dos controladores (DRY).
     */
    public function scopeBuscar($query, string $termino)
    {
        return $query->where(function ($q) use ($termino) {
            $q->whereRaw('unaccent(nombre_principal) ILIKE unaccent(?)', ["%{$termino}%"])
                ->orWhereRaw(
                    "EXISTS (
                        SELECT 1 FROM jsonb_array_elements_text(COALESCE(nombres_alternativos, '[]'::jsonb)) AS alt
                        WHERE unaccent(alt) ILIKE unaccent(?)
                    )",
                    ["%{$termino}%"]
                );
        });
    }
}
