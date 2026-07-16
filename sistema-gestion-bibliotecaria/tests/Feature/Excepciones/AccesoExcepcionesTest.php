<?php

// Origen: Plan de Implementación v2, Módulo 6 — Excepciones y restricciones. RN-10: el CRUD de
// ExcepcionAutorizada está reservado a Administrador (a diferencia de Catálogo/Socios/Préstamos,
// que también permiten Personal). Criterio de aceptación 1 (literal): "Un usuario con rol Personal
// no puede acceder a la pantalla de creación de excepciones. La ruta devuelve error de
// autorización." Mismo patrón que AccesoPrestamosTest/AccesoCatalogoTest/AccesoSociosTest.

namespace Tests\Feature\Excepciones;

use App\Models\ExcepcionAutorizada;
use App\Models\Socio;
use App\Models\TipoSocio;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class AccesoExcepcionesTest extends TestCase
{
    use RefreshDatabase;

    /** Criterio de aceptación 1 (literal del plan). */
    public function test_un_usuario_personal_no_puede_acceder_a_la_pantalla_de_creacion_de_excepciones(): void
    {
        $personal = User::factory()->create(['rol' => User::ROL_PERSONAL]);

        $respuesta = $this->actingAs($personal)->get(route('excepciones.create'));

        $respuesta->assertForbidden();
    }

    public function test_un_usuario_personal_no_puede_acceder_al_listado_de_excepciones(): void
    {
        $personal = User::factory()->create(['rol' => User::ROL_PERSONAL]);

        $respuesta = $this->actingAs($personal)->get(route('excepciones.index'));

        $respuesta->assertForbidden();
    }

    public function test_un_usuario_personal_no_puede_crear_una_excepcion_por_post_directo(): void
    {
        $personal = User::factory()->create(['rol' => User::ROL_PERSONAL]);
        $socio = $this->crearSocio();

        $respuesta = $this->actingAs($personal)->post(route('excepciones.store'), [
            'tipo' => ExcepcionAutorizada::TIPO_EXENCION_RESTRICCION,
            'entidad_id' => $socio->id,
            'motivo' => 'Intento no autorizado.',
            'fecha_inicio' => now()->toDateString(),
        ]);

        $respuesta->assertForbidden();
        $this->assertSame(0, ExcepcionAutorizada::count());
    }

    public function test_un_usuario_personal_no_puede_revocar_una_excepcion(): void
    {
        $admin = User::factory()->create(['rol' => User::ROL_ADMINISTRADOR]);
        $personal = User::factory()->create(['rol' => User::ROL_PERSONAL]);
        $excepcion = $this->crearExcepcionVigente($admin);

        $respuesta = $this->actingAs($personal)->patch(route('excepciones.revocar', $excepcion));

        $respuesta->assertForbidden();
        $this->assertSame(ExcepcionAutorizada::ESTADO_VIGENTE, $excepcion->fresh()->estado);
    }

    public function test_un_administrador_si_puede_acceder_a_la_pantalla_de_creacion_de_excepciones(): void
    {
        $admin = User::factory()->create(['rol' => User::ROL_ADMINISTRADOR]);

        $respuesta = $this->actingAs($admin)->get(route('excepciones.create'));

        $respuesta->assertOk();
    }

    public function test_un_visitante_no_autenticado_es_redirigido_al_login(): void
    {
        $respuesta = $this->get(route('excepciones.create'));

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

    private function crearExcepcionVigente(User $admin): ExcepcionAutorizada
    {
        return ExcepcionAutorizada::create([
            'tipo' => ExcepcionAutorizada::TIPO_EXENCION_RESTRICCION,
            'entidad_afectada_type' => Socio::class,
            'entidad_afectada_id' => $this->crearSocio()->id,
            'autorizado_por' => $admin->id,
            'fecha_autorizacion' => now(),
            'motivo' => 'Excepción de prueba.',
            'fecha_inicio' => now()->toDateString(),
            'fecha_fin' => null,
            'estado' => ExcepcionAutorizada::ESTADO_VIGENTE,
        ]);
    }
}
