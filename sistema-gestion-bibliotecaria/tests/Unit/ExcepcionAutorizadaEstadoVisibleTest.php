<?php

// Origen: BRIEFING-MODULO-6-EXCEPCIONES-RESTRICCIONES.md, Decisión D-15. Test unitario puro (sin
// RefreshDatabase ni arranque de base de datos): estadoVisible() solo lee los atributos en memoria
// del modelo (estado, fecha_fin) — no depende de ninguna consulta. Mismo criterio que
// ReservaCalcularFechaLimiteRetiroTest para Reserva::calcularFechaLimiteRetiro() (Módulo 5).

namespace Tests\Unit;

use App\Models\ExcepcionAutorizada;
use PHPUnit\Framework\TestCase;

class ExcepcionAutorizadaEstadoVisibleTest extends TestCase
{
    public function test_vigente_sin_fecha_de_fin_se_ve_como_vigente(): void
    {
        $excepcion = new ExcepcionAutorizada([
            'estado' => ExcepcionAutorizada::ESTADO_VIGENTE,
            'fecha_fin' => null,
        ]);

        $this->assertSame(ExcepcionAutorizada::ESTADO_VIGENTE, $excepcion->estadoVisible());
    }

    public function test_vigente_con_fecha_de_fin_futura_se_ve_como_vigente(): void
    {
        $excepcion = new ExcepcionAutorizada([
            'estado' => ExcepcionAutorizada::ESTADO_VIGENTE,
            'fecha_fin' => now()->addDays(10)->toDateString(),
        ]);

        $this->assertSame(ExcepcionAutorizada::ESTADO_VIGENTE, $excepcion->estadoVisible());
    }

    public function test_vigente_con_fecha_de_fin_hoy_todavia_se_ve_como_vigente(): void
    {
        $excepcion = new ExcepcionAutorizada([
            'estado' => ExcepcionAutorizada::ESTADO_VIGENTE,
            'fecha_fin' => now()->toDateString(),
        ]);

        $this->assertSame(ExcepcionAutorizada::ESTADO_VIGENTE, $excepcion->estadoVisible());
    }

    /** Decisión D-15: la columna sigue en 'vigente', pero se ve como 'vencida' al leer. */
    public function test_vigente_con_fecha_de_fin_pasada_se_ve_como_vencida(): void
    {
        $excepcion = new ExcepcionAutorizada([
            'estado' => ExcepcionAutorizada::ESTADO_VIGENTE,
            'fecha_fin' => now()->subDay()->toDateString(),
        ]);

        $this->assertSame(ExcepcionAutorizada::ESTADO_VENCIDA, $excepcion->estadoVisible());
    }

    /** Una revocación explícita siempre prevalece, incluso si la fecha de fin no pasó todavía. */
    public function test_revocada_se_ve_como_revocada_aunque_la_fecha_de_fin_no_haya_pasado(): void
    {
        $excepcion = new ExcepcionAutorizada([
            'estado' => ExcepcionAutorizada::ESTADO_REVOCADA,
            'fecha_fin' => now()->addDays(10)->toDateString(),
        ]);

        $this->assertSame(ExcepcionAutorizada::ESTADO_REVOCADA, $excepcion->estadoVisible());
    }
}
