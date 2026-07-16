<?php

// Origen: Plan de Implementación v2, Módulo 6 — Excepciones y restricciones, CU-3. A diferencia
// del CRUD de ExcepcionAutorizada (RN-10, solo Administrador), las Restricciones manuales admiten
// también Personal — Riesgo R-4 del briefing (dos middlewares de rol distintos dentro del mismo
// módulo). Mismo patrón que AccesoPrestamosTest/AccesoCatalogoTest/AccesoSociosTest.

namespace Tests\Feature\Restricciones;

use App\Models\RestriccionSocio;
use App\Models\Socio;
use App\Models\TipoSocio;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class AccesoRestriccionesTest extends TestCase
{
    use RefreshDatabase;

    public function test_un_voluntario_no_puede_acceder_al_listado_de_restricciones_de_un_socio(): void
    {
        $voluntario = User::factory()->create(['rol' => User::ROL_VOLUNTARIO]);
        $socio = $this->crearSocio();

        $respuesta = $this->actingAs($voluntario)->get(route('restricciones.index', $socio));

        $respuesta->assertForbidden();
    }

    public function test_un_voluntario_no_puede_crear_una_restriccion_manual(): void
    {
        $voluntario = User::factory()->create(['rol' => User::ROL_VOLUNTARIO]);
        $socio = $this->crearSocio();

        $respuesta = $this->actingAs($voluntario)->post(route('restricciones.store', $socio), [
            'observaciones' => 'Intento no autorizado.',
            'fecha_fin' => now()->addDays(10)->toDateString(),
        ]);

        $respuesta->assertForbidden();
        $this->assertSame(0, RestriccionSocio::count());
    }

    public function test_un_usuario_personal_si_puede_acceder_al_listado_de_restricciones(): void
    {
        $personal = User::factory()->create(['rol' => User::ROL_PERSONAL]);
        $socio = $this->crearSocio();

        $respuesta = $this->actingAs($personal)->get(route('restricciones.index', $socio));

        $respuesta->assertOk();
    }

    public function test_un_usuario_personal_si_puede_crear_una_restriccion_manual(): void
    {
        $personal = User::factory()->create(['rol' => User::ROL_PERSONAL]);
        $socio = $this->crearSocio();

        $respuesta = $this->actingAs($personal)->post(route('restricciones.store', $socio), [
            'observaciones' => 'Restricción cargada por Personal.',
            'fecha_fin' => now()->addDays(10)->toDateString(),
        ]);

        $respuesta->assertRedirect(route('restricciones.index', $socio));
        $this->assertSame(1, RestriccionSocio::count());
    }

    public function test_un_visitante_no_autenticado_es_redirigido_al_login(): void
    {
        $socio = $this->crearSocio();

        $respuesta = $this->get(route('restricciones.index', $socio));

        $respuesta->assertRedirect('/login');
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
