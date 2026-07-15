<?php

// Origen: BRIEFING-MODULO-5-RENOVACIONES-RESERVAS.md, Decisión D-13 y Paso 6. Test unitario puro
// (sin RefreshDatabase ni arranque de base de datos): Reserva::calcularFechaLimiteRetiro() es un
// método estático que no depende de Eloquent ni de la base de datos, solo de sus argumentos.
//
// Las fechas de referencia se construyen siempre a partir de Carbon::now()->startOfWeek() (el lunes
// de la semana actual, comportamiento por defecto de Carbon) en lugar de fechas de calendario fijas,
// para no depender de qué día de la semana cae una fecha hardcodeada — los tres casos son válidos
// sin importar cuándo se ejecute la suite.

namespace Tests\Unit;

use App\Models\Reserva;
use Illuminate\Support\Carbon;
use PHPUnit\Framework\TestCase;

class ReservaCalcularFechaLimiteRetiroTest extends TestCase
{
    private const DIAS_ATENCION = ['lunes', 'martes', 'miercoles', 'jueves', 'viernes'];

    /**
     * Caso "mismo día con margen suficiente": una ventana chica que no llega a cruzar la
     * medianoche debe resolverse como una simple suma de horas.
     */
    public function test_alerta_con_margen_suficiente_en_el_mismo_dia(): void
    {
        $lunes8 = Carbon::now()->startOfWeek()->setTime(8, 0);

        $resultado = Reserva::calcularFechaLimiteRetiro($lunes8, 5, self::DIAS_ATENCION);

        $this->assertTrue($resultado->equalTo($lunes8->copy()->addHours(5)));
    }

    /**
     * Caso "cruce de fin de semana": una alerta el viernes con una ventana de 48 horas de atención
     * debe saltar sábado y domingo por completo. Viernes aporta 14h (10:00 a medianoche), lunes
     * aporta 24h, y las 10h restantes se consumen el martes a la mañana — total 48h de atención.
     */
    public function test_alerta_el_viernes_salta_el_fin_de_semana_completo(): void
    {
        $viernes10 = Carbon::now()->startOfWeek()->addDays(4)->setTime(10, 0);

        $resultado = Reserva::calcularFechaLimiteRetiro($viernes10, 48, self::DIAS_ATENCION);

        $esperado = Carbon::now()->startOfWeek()->addDays(8)->setTime(10, 0); // martes siguiente
        $this->assertTrue($resultado->equalTo($esperado));
    }

    /**
     * Caso "último día de atención de la semana": una alerta el jueves a última hora, con ventana
     * de 48 horas, consume 1h el jueves, 24h el viernes, salta sábado y domingo, y termina de
     * consumir las 23h restantes el lunes siguiente.
     */
    public function test_alerta_el_jueves_a_ultima_hora_termina_el_lunes_siguiente(): void
    {
        $jueves23 = Carbon::now()->startOfWeek()->addDays(3)->setTime(23, 0);

        $resultado = Reserva::calcularFechaLimiteRetiro($jueves23, 48, self::DIAS_ATENCION);

        $esperado = Carbon::now()->startOfWeek()->addDays(7)->setTime(23, 0); // lunes siguiente
        $this->assertTrue($resultado->equalTo($esperado));
    }

    /**
     * Caso de borde: si el propio día de la alerta no es de atención (ej. una reserva marcada un
     * domingo por un proceso batch), el método salta al mismo horario del día siguiente sin
     * consumir ninguna hora de la ventana (preserva la hora del día, no la resetea a medianoche) —
     * ver el comentario del algoritmo en Reserva::calcularFechaLimiteRetiro().
     */
    public function test_alerta_en_dia_no_habil_no_consume_horas_hasta_el_proximo_dia_de_atencion(): void
    {
        $domingo15 = Carbon::now()->startOfWeek()->addDays(6)->setTime(15, 0);

        $resultado = Reserva::calcularFechaLimiteRetiro($domingo15, 5, self::DIAS_ATENCION);

        // Domingo no cuenta: salta al lunes a la misma hora (15:00) y desde ahí consume las 5 horas.
        $esperado = Carbon::now()->startOfWeek()->addDays(7)->setTime(20, 0);
        $this->assertTrue($resultado->equalTo($esperado));
    }
}
