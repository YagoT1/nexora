<?php

// Origen: Modelo de Dominio v2, 1.5 "Ejemplar". D-09 (estado parcialmente derivado). RN-04 (invariante
// de circulación, verificación cruzada entre los 4 tipos de movimiento — ver nota en migración
// 2024_01_01_000100 sobre el tradeoff aceptado en DA-09: el índice único parcial de PostgreSQL
// protege cada tabla de movimiento individualmente; esta clase es la que implementa la verificación
// CRUZADA entre las cuatro tablas que exige RN-04, como "Nivel 2" de DA-09).

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class Ejemplar extends Model
{
    protected $table = 'ejemplares';

    protected $fillable = [
        'libro_id',
        'estado_manual',
        'modalidad_acceso',
        'condicion_fisica',
        'fecha_ingreso',
        'origen',
    ];

    protected function casts(): array
    {
        return ['fecha_ingreso' => 'date'];
    }

    public function libro()
    {
        return $this->belongsTo(Libro::class);
    }

    public function prestamosDomiciliarios()
    {
        return $this->hasMany(PrestamoDomiciliario::class);
    }

    /**
     * RN-04: verificación cruzada de la invariante de circulación. Debe consultarse ANTES de crear
     * cualquier movimiento nuevo (préstamo domiciliario, institucional, interno o custodia externa),
     * ademas de confiar en el indice unico parcial de cada tabla (DA-09 Nivel 1). Este metodo es el
     * "Nivel 2" de DA-09 y es el UNICO lugar que efectivamente cubre el caso cruzado (ej.: que el mismo
     * ejemplar no pueda estar en un prestamo domiciliario Y en una custodia externa al mismo tiempo).
     */
    public function tieneMovimientoActivo(): bool
    {
        return $this->prestamosDomiciliarios()
                ->whereIn('estado', ['activo', 'atrasado'])
                ->exists()
            || $this->belongsToMany(PrestamoInstitucional::class, 'ejemplares_prestamo_institucional')
                ->wherePivotNull('fecha_devolucion_efectiva')
                ->exists()
            || $this->belongsToMany(MovimientoInterno::class, 'ejemplares_movimiento_interno')
                ->wherePivotNull('fecha_devolucion_efectiva')
                ->exists()
            || $this->belongsToMany(CustodiaExterna::class, 'ejemplares_custodia_externa')
                ->wherePivotNull('fecha_devolucion_efectiva')
                ->exists();
    }

    /**
     * D-09: estado operativo. Los estados derivados (prestado / en_movimiento_interno /
     * en_custodia_externa) NUNCA se leen de una columna: se calculan aquí. Disponible es el default
     * cuando no hay estado manual ni movimiento activo.
     */
    public function estadoActual(): string
    {
        if ($this->estado_manual) {
            return $this->estado_manual; // en_reparacion | extraviado
        }

        if ($this->prestamosDomiciliarios()->whereIn('estado', ['activo', 'atrasado'])->exists()) {
            return 'prestado';
        }

        if ($this->belongsToMany(MovimientoInterno::class, 'ejemplares_movimiento_interno')
            ->wherePivotNull('fecha_devolucion_efectiva')->exists()) {
            return 'en_movimiento_interno';
        }

        if ($this->belongsToMany(CustodiaExterna::class, 'ejemplares_custodia_externa')
            ->wherePivotNull('fecha_devolucion_efectiva')->exists()) {
            return 'en_custodia_externa';
        }

        return 'disponible';
    }
}
