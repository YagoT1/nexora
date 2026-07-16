<?php

// Origen: Plan de Implementación v2, Módulo 6 — Excepciones y restricciones, CU-3. Ver
// Fase 6 - Development/BRIEFING-MODULO-6-EXCEPCIONES-RESTRICCIONES.md, Paso 4.

namespace Tests\Feature\Restricciones;

use App\Models\RestriccionSocio;
use App\Models\Socio;
use App\Models\TipoSocio;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class RestriccionSocioTest extends TestCase
{
    use RefreshDatabase;

    /**
     * CU-3: "completa socio, motivo (observaciones) y fecha de fin; el sistema fija tipo = manual,
     * fecha_inicio = hoy, y generada_por_usuario_id al usuario autenticado."
     */
    public function test_el_alta_manual_fija_tipo_fecha_inicio_y_generador_automaticamente(): void
    {
        $personal = User::factory()->create(['rol' => User::ROL_PERSONAL]);
        $socio = $this->crearSocio();

        $respuesta = $this->actingAs($personal)->post(route('restricciones.store', $socio), [
            'observaciones' => 'Deterioro reiterado de material prestado.',
            'fecha_fin' => now()->addDays(15)->toDateString(),
        ]);

        $respuesta->assertRedirect(route('restricciones.index', $socio));

        $restriccion = RestriccionSocio::firstOrFail();
        $this->assertSame($socio->id, $restriccion->socio_id);
        $this->assertSame(RestriccionSocio::TIPO_MANUAL, $restriccion->tipo);
        $this->assertSame(now()->toDateString(), $restriccion->fecha_inicio->toDateString());
        $this->assertSame(now()->addDays(15)->toDateString(), $restriccion->fecha_fin->toDateString());
        $this->assertSame('Deterioro reiterado de material prestado.', $restriccion->observaciones);
        $this->assertSame($personal->id, $restriccion->generada_por_usuario_id);
    }

    public function test_no_se_puede_crear_una_restriccion_manual_si_ya_existe_una_restriccion_activa(): void
    {
        $personal = User::factory()->create(['rol' => User::ROL_PERSONAL]);
        $socio = $this->crearSocio();
        RestriccionSocio::create([
            'socio_id' => $socio->id,
            'tipo' => RestriccionSocio::TIPO_AUTOMATICA,
            'fecha_inicio' => now()->toDateString(),
            'fecha_fin' => now()->addDays(5)->toDateString(),
            'dias_atraso_origen' => 5,
        ]);

        $respuesta = $this->actingAs($personal)->post(route('restricciones.store', $socio), [
            'observaciones' => 'Segunda restricción, no debería crearse.',
            'fecha_fin' => now()->addDays(10)->toDateString(),
        ]);

        $respuesta->assertSessionHasErrors('fecha_fin');
        $this->assertSame(1, RestriccionSocio::count());
    }

    public function test_la_fecha_de_fin_es_obligatoria(): void
    {
        $personal = User::factory()->create(['rol' => User::ROL_PERSONAL]);
        $socio = $this->crearSocio();

        $respuesta = $this->actingAs($personal)->post(route('restricciones.store', $socio), [
            'observaciones' => 'Sin fecha de fin.',
        ]);

        $respuesta->assertSessionHasErrors('fecha_fin');
        $this->assertSame(0, RestriccionSocio::count());
    }

    /** Listado de restricciones activas/históricas por socio (plan, Paso 4). */
    public function test_el_listado_muestra_tanto_restricciones_activas_como_historicas(): void
    {
        $personal = User::factory()->create(['rol' => User::ROL_PERSONAL]);
        $socio = $this->crearSocio();
        $historica = RestriccionSocio::create([
            'socio_id' => $socio->id,
            'tipo' => RestriccionSocio::TIPO_AUTOMATICA,
            'fecha_inicio' => now()->subDays(30)->toDateString(),
            'fecha_fin' => now()->subDays(20)->toDateString(),
            'dias_atraso_origen' => 3,
        ]);

        $respuesta = $this->actingAs($personal)->get(route('restricciones.index', $socio));

        $respuesta->assertOk();
        $respuesta->assertViewHas('restricciones', function ($restricciones) use ($historica) {
            return $restricciones->contains('id', $historica->id);
        });
    }

    private function crearSocio(): Socio
    {
        $tipoSocio = TipoSocio::create([
            'nombre' => 'Estándar de prueba '.uniqid(),
            'limite_prestamos_simultaneos' => 3,
            'sujeto_a_restriccion_automatica' => true,
        ]);

        return Socio::create([
            'nombre_principal' => 'Socio de prueba '.uniqid(),
            'fecha_alta' => '2020-01-01',
            'tipo_socio_id' => $tipoSocio->id,
        ]);
    }
}
