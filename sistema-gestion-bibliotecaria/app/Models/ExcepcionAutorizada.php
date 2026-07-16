<?php

// Origen: Modelo de Dominio v2, 4.1. D-03, RN-10, RN-11. Auditado (RN-11: trazabilidad completa).

namespace App\Models;

use App\Support\Auditing\Auditable;
use Illuminate\Database\Eloquent\Model;

class ExcepcionAutorizada extends Model
{
    use Auditable;

    protected $table = 'excepciones_autorizadas';

    public const TIPO_EXENCION_RESTRICCION = 'exencion_restriccion_atraso';
    public const TIPO_LIMITE_ESPECIAL = 'limite_prestamo_especial';
    public const TIPO_AUTORIZACION_MATERIAL_RESTRINGIDO = 'autorizacion_salida_material_restringido';

    // Origen: Módulo 6, Decisión D-14. Formaliza como constantes los tres valores de `estado` que
    // ya estaban documentados como comentario en la migración. 'vencida' nunca se escribe en la
    // columna — es un estado derivado, ver estadoVisible() (Decisión D-15).
    public const ESTADO_VIGENTE = 'vigente';
    public const ESTADO_VENCIDA = 'vencida';
    public const ESTADO_REVOCADA = 'revocada';

    /**
     * Origen: Módulo 6, Paso 3. Modelo de Dominio v2, 4.1 (tabla de tipos de excepción): cada tipo
     * está asociado a una única clase de entidad afectada. Única fuente de verdad de ese mapeo
     * (D-03: un único mecanismo de excepción con tipos, no una entidad separada por cada uno) —
     * usada por ExcepcionController para decidir qué se busca según el tipo elegido.
     */
    public const ENTIDADES_POR_TIPO = [
        self::TIPO_EXENCION_RESTRICCION => Socio::class,
        self::TIPO_LIMITE_ESPECIAL => Socio::class,
        self::TIPO_AUTORIZACION_MATERIAL_RESTRINGIDO => Ejemplar::class,
    ];

    // Origen: Módulo 6, Paso 3. Mismo criterio que Ejemplar::ETIQUETAS_ESTADO/ETIQUETAS_MODALIDAD
    // (Módulo 2): única fuente de verdad para las etiquetas en español, reutilizada por el <select>
    // de excepciones/create.blade.php y por la columna "Tipo" de excepciones/index.blade.php.
    public const ETIQUETAS_TIPO = [
        self::TIPO_EXENCION_RESTRICCION => 'Exención de restricción por atraso',
        self::TIPO_LIMITE_ESPECIAL => 'Límite de préstamo especial',
        self::TIPO_AUTORIZACION_MATERIAL_RESTRINGIDO => 'Autorización de salida de material restringido',
    ];

    protected $fillable = [
        'tipo', 'entidad_afectada_type', 'entidad_afectada_id', 'autorizado_por',
        'fecha_autorizacion', 'motivo', 'fecha_inicio', 'fecha_fin', 'estado',
        'revocado_por', 'fecha_revocacion',
    ];

    protected function casts(): array
    {
        return [
            'fecha_autorizacion' => 'date',
            'fecha_inicio' => 'date',
            'fecha_fin' => 'date',
            'fecha_revocacion' => 'date',
        ];
    }

    public function entidadAfectada()
    {
        return $this->morphTo(__FUNCTION__, 'entidad_afectada_type', 'entidad_afectada_id');
    }

    public function autorizadoPor()
    {
        return $this->belongsTo(User::class, 'autorizado_por');
    }

    // Origen: Módulo 6, Decisión D-14. Inversa de autorizadoPor(): faltaba pese a que la columna
    // `revocado_por` y su foreignId a `users` ya existían desde el Módulo 1.
    public function revocadoPor()
    {
        return $this->belongsTo(User::class, 'revocado_por');
    }

    public function estaVigente(): bool
    {
        if ($this->estado !== self::ESTADO_VIGENTE) {
            return false;
        }

        return $this->fecha_fin === null || $this->fecha_fin->isFuture() || $this->fecha_fin->isToday();
    }

    /**
     * Origen: Módulo 6, Decisión D-15 (análoga a D-09 / Ejemplar::estadoActual()). El criterio de
     * aceptación 5 exige que una excepción con fecha de fin pasada "aparezca" como Vencida, pero
     * ninguna tarea programada reescribe la columna `estado` — se deriva en el momento de la
     * lectura, evitando una dependencia artificial hacia el Módulo 7 (Tareas programadas).
     */
    public function estadoVisible(): string
    {
        if ($this->estado === self::ESTADO_REVOCADA) {
            return self::ESTADO_REVOCADA;
        }

        if ($this->fecha_fin !== null && $this->fecha_fin->isPast() && ! $this->fecha_fin->isToday()) {
            return self::ESTADO_VENCIDA;
        }

        return self::ESTADO_VIGENTE;
    }

    /**
     * Origen: Módulo 6, Decisión D-18. Centraliza la consulta "¿esta entidad tiene una excepción
     * vigente de este tipo?", antes duplicada como método privado en PrestamoController (Módulo 4)
     * y de forma equivalente en Ejemplar::puedeSalirDeLaBiblioteca() (Módulo 2, RN-09).
     */
    public static function vigentePara(\Illuminate\Database\Eloquent\Model $entidad, string $tipo): bool
    {
        return static::where('entidad_afectada_type', $entidad::class)
            ->where('entidad_afectada_id', $entidad->getKey())
            ->where('tipo', $tipo)
            ->get()
            ->contains(fn (self $excepcion) => $excepcion->estaVigente());
    }
}
