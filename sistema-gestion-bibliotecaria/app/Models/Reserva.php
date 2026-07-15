<?php

// Origen: Modelo de Dominio v2, 3.3 "Reserva". Lógica completa: Módulo 5
// (BRIEFING-MODULO-5-RENOVACIONES-RESERVAS.md).

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Support\Carbon;

class Reserva extends Model
{
    // Origen: Módulo 5, Paso 1. Los 5 estados ya estaban documentados como comentario en la
    // migración de creación de esta tabla (Módulo 1) — estas constantes no cambian el esquema,
    // solo evitan repetir los strings mágicos (mismo patrón que Ejemplar::ESTADO_*,
    // PrestamoDomiciliario::ESTADO_*).
    public const ESTADO_PENDIENTE = 'pendiente';

    public const ESTADO_PERSONAL_ALERTADO = 'personal_alertado';

    public const ESTADO_RETIRADA = 'retirada';

    public const ESTADO_VENCIDA_POR_NO_RETIRO = 'vencida_por_no_retiro';

    public const ESTADO_CANCELADA = 'cancelada';

    // Una reserva "activa" (todavía no resuelta, en cualquiera de los dos sentidos posibles: retiro
    // o vencimiento) es la que está 'pendiente' o ya 'personal_alertado'. Mismo criterio de
    // agrupación que PrestamoDomiciliario::ESTADOS_ABIERTOS. Se reutiliza en dos lugares distintos
    // del dominio: RN-03 (bloqueo de renovación) y el criterio de aceptación "un socio no puede
    // tener dos reservas activas para el mismo Libro" — ambos hablan del mismo concepto.
    public const ESTADOS_ACTIVOS = [self::ESTADO_PENDIENTE, self::ESTADO_PERSONAL_ALERTADO];

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

    /**
     * RN-05 + Decisión D-13 (BRIEFING-MODULO-5-RENOVACIONES-RESERVAS.md, sección 7): la ventana de
     * retiro se mide en "horas de atención al público", no en horas de reloj corridas. El dominio
     * solo define qué DÍAS de la semana son de atención (ParametroConfiguracion::
     * DIAS_ATENCION_AL_PUBLICO), sin un horario de apertura/cierre dentro del día — no existe ese
     * parámetro en ningún documento del proyecto. Interpretación adoptada, la única consistente con
     * los datos disponibles sin inventar un horario no solicitado: cada día de atención cuenta como
     * un bloque continuo de 24 horas hacia la ventana; cada día que no es de atención se salta por
     * completo (no consume horas de la ventana), preservando la hora del día al reanudar.
     *
     * Método puro (no depende de la base de datos ni del estado de esta instancia) para que sea
     * testeable unitariamente sin RefreshDatabase.
     *
     * @param  array<int, string>  $diasAtencion  Nombres de días en español, minúsculas, sin acentos
     *                                            (formato ya usado por el parámetro sembrado:
     *                                            'lunes,martes,miercoles,jueves,viernes').
     */
    public static function calcularFechaLimiteRetiro(Carbon $fechaAlerta, int $horasVentana, array $diasAtencion): Carbon
    {
        $nombresDia = [
            0 => 'domingo', 1 => 'lunes', 2 => 'martes', 3 => 'miercoles',
            4 => 'jueves', 5 => 'viernes', 6 => 'sabado',
        ];

        $cursor = $fechaAlerta->copy();
        $segundosRestantes = $horasVentana * 3600;

        while ($segundosRestantes > 0) {
            $esDiaDeAtencion = in_array($nombresDia[(int) $cursor->dayOfWeek], $diasAtencion, true);

            if (! $esDiaDeAtencion) {
                // Día que no cuenta: salta al mismo horario del día siguiente sin consumir ventana.
                $cursor = $cursor->copy()->addDay();

                continue;
            }

            $inicioDelDiaSiguiente = $cursor->copy()->addDay()->startOfDay();
            $segundosHastaMedianoche = $cursor->diffInSeconds($inicioDelDiaSiguiente);
            $segundosAConsumir = min($segundosRestantes, $segundosHastaMedianoche);
            $cursor = $cursor->copy()->addSeconds($segundosAConsumir);
            $segundosRestantes -= $segundosAConsumir;
        }

        return $cursor;
    }
}
