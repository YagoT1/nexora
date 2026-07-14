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

    // Constantes agregadas en Módulo 2 (Catálogo) para no repetir strings mágicos en
    // EjemplarRequest/vistas — mismo patrón que User::ROL_*/ROLES. Los valores coinciden
    // exactamente con los ya usados por la migración 2024_01_01_000090_create_ejemplares_table.
    public const ESTADO_MANUAL_EN_REPARACION = 'en_reparacion';
    public const ESTADO_MANUAL_EXTRAVIADO = 'extraviado';

    public const ESTADOS_MANUALES = [
        self::ESTADO_MANUAL_EN_REPARACION,
        self::ESTADO_MANUAL_EXTRAVIADO,
    ];

    // Estados derivados (D-09) — nunca se asignan directamente, los calcula estadoActual().
    public const ESTADO_DISPONIBLE = 'disponible';
    public const ESTADO_PRESTADO = 'prestado';
    public const ESTADO_EN_MOVIMIENTO_INTERNO = 'en_movimiento_interno';
    public const ESTADO_EN_CUSTODIA_EXTERNA = 'en_custodia_externa';

    // Origen: Plan de Implementación v2, Módulo 2, "Búsqueda de catálogo: ... estado" (Paso 5).
    // Universo completo de valores que puede devolver estadoActual(), para usar en el filtro de
    // búsqueda y en cualquier <select> que necesite listarlos todos (manuales + derivados).
    public const ESTADOS_OPERATIVOS = [
        self::ESTADO_DISPONIBLE,
        self::ESTADO_MANUAL_EN_REPARACION,
        self::ESTADO_MANUAL_EXTRAVIADO,
        self::ESTADO_PRESTADO,
        self::ESTADO_EN_MOVIMIENTO_INTERNO,
        self::ESTADO_EN_CUSTODIA_EXTERNA,
    ];

    public const MODALIDAD_LIBRE_CIRCULACION = 'libre_circulacion';
    public const MODALIDAD_SOLO_SALA = 'solo_sala';
    public const MODALIDAD_RESTRINGIDO = 'restringido_a_autorizacion';

    public const MODALIDADES_ACCESO = [
        self::MODALIDAD_LIBRE_CIRCULACION,
        self::MODALIDAD_SOLO_SALA,
        self::MODALIDAD_RESTRINGIDO,
    ];

    // Origen: Módulo 2, Paso 5 (buscador) y Paso 6 (vista de detalle de Libro). Única fuente de
    // verdad para las etiquetas en español de estado/modalidad — evita repetir el mismo array
    // literal en cada vista Blade que necesite mostrarlas (index y show de Libro, por ahora).
    public const ETIQUETAS_ESTADO = [
        self::ESTADO_DISPONIBLE => 'Disponible',
        self::ESTADO_PRESTADO => 'Prestado',
        self::ESTADO_EN_MOVIMIENTO_INTERNO => 'En movimiento interno',
        self::ESTADO_EN_CUSTODIA_EXTERNA => 'En custodia externa',
        self::ESTADO_MANUAL_EN_REPARACION => 'En reparación',
        self::ESTADO_MANUAL_EXTRAVIADO => 'Extraviado',
    ];

    public const ETIQUETAS_MODALIDAD = [
        self::MODALIDAD_LIBRE_CIRCULACION => 'Libre circulación',
        self::MODALIDAD_SOLO_SALA => 'Solo en sala',
        self::MODALIDAD_RESTRINGIDO => 'Restringido a autorización',
    ];

    public const ORIGEN_COMPRA = 'compra';
    public const ORIGEN_DONACION = 'donacion';
    public const ORIGEN_OTRO = 'otro';

    public const ORIGENES = [
        self::ORIGEN_COMPRA,
        self::ORIGEN_DONACION,
        self::ORIGEN_OTRO,
    ];

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
     * Relaciones agregadas en Módulo 2 (Paso 5, búsqueda de catálogo): antes existían solo como
     * belongsToMany() anónimos e inline dentro de tieneMovimientoActivo()/estadoActual(), lo cual
     * alcanzaba para esos dos métodos pero no permite construir un whereHas() desde Libro para
     * filtrar por estado. Nombrarlas también las deja listas para los Módulos 4/5 (préstamos
     * institucionales, movimientos internos, custodia externa), que de todos modos las van a
     * necesitar. No cambia ningún comportamiento existente — ver refactor de ambos métodos abajo.
     */
    public function prestamosInstitucionales()
    {
        return $this->belongsToMany(PrestamoInstitucional::class, 'ejemplares_prestamo_institucional')
            ->withPivot('fecha_devolucion_efectiva');
    }

    public function movimientosInternos()
    {
        return $this->belongsToMany(MovimientoInterno::class, 'ejemplares_movimiento_interno')
            ->withPivot('fecha_devolucion_efectiva');
    }

    public function custodiasExternas()
    {
        return $this->belongsToMany(CustodiaExterna::class, 'ejemplares_custodia_externa')
            ->withPivot('fecha_devolucion_efectiva');
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
            || $this->prestamosInstitucionales()->wherePivotNull('fecha_devolucion_efectiva')->exists()
            || $this->movimientosInternos()->wherePivotNull('fecha_devolucion_efectiva')->exists()
            || $this->custodiasExternas()->wherePivotNull('fecha_devolucion_efectiva')->exists();
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
            return self::ESTADO_PRESTADO;
        }

        if ($this->movimientosInternos()->wherePivotNull('fecha_devolucion_efectiva')->exists()) {
            return self::ESTADO_EN_MOVIMIENTO_INTERNO;
        }

        if ($this->custodiasExternas()->wherePivotNull('fecha_devolucion_efectiva')->exists()) {
            return self::ESTADO_EN_CUSTODIA_EXTERNA;
        }

        return self::ESTADO_DISPONIBLE;
    }
}
