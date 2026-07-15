<?php

// Origen: Modelo de Dominio v2, 3.1 "Préstamo domiciliario". RN-01, RN-02, RN-04, RN-12, RN-13, RN-18, RN-19.
// La lógica de negocio completa (verificación de límite, restricciones, generación de restricción
// automática al devolver con atraso) se implementa en el Módulo 4 — Préstamos y devoluciones
// (BRIEFING-MODULO-4-PRESTAMOS.md). Este modelo define estructura, relaciones y las constantes de
// estado que consume esa lógica.

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class PrestamoDomiciliario extends Model
{
    // Origen: Módulo 4, Paso 1. 'Atrasado' no lo marca ningún proceso automático todavía (esa tarea
    // programada es Módulo 7, ver BRIEFING-MODULO-4-PRESTAMOS.md, riesgo R-1) — el atraso real se
    // calcula por fecha en el momento de la devolución, no dependiendo de este campo. 'Activo' y
    // 'Atrasado' se tratan como equivalentes ("préstamo abierto") en toda consulta de este módulo.
    public const ESTADO_ACTIVO = 'activo';

    public const ESTADO_ATRASADO = 'atrasado';

    public const ESTADO_DEVUELTO = 'devuelto';

    public const ESTADOS_ABIERTOS = [self::ESTADO_ACTIVO, self::ESTADO_ATRASADO];

    protected $table = 'prestamos_domiciliarios';

    protected $fillable = [
        'ejemplar_id', 'socio_id', 'fecha_registro', 'fecha_prestamo', 'fecha_vencimiento',
        'fecha_devolucion_efectiva', 'estado', 'registrado_por', 'es_excepcion_de_limite',
        'motivo_excepcion_limite',
    ];

    protected function casts(): array
    {
        return [
            'fecha_registro' => 'datetime',
            'fecha_prestamo' => 'date',
            'fecha_vencimiento' => 'date',
            'fecha_devolucion_efectiva' => 'date',
            'es_excepcion_de_limite' => 'boolean',
        ];
    }

    public function ejemplar()
    {
        return $this->belongsTo(Ejemplar::class);
    }

    public function socio()
    {
        return $this->belongsTo(Socio::class);
    }

    public function renovaciones()
    {
        return $this->hasMany(Renovacion::class);
    }
}
