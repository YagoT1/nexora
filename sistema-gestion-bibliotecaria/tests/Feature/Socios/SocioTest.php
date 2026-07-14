<?php

// Origen: Plan de Implementación v2, Módulo 3 — Socios, criterios de aceptación: CRUD de Socio y
// búsqueda tolerante a variaciones de nombre (mayúsculas/minúsculas y acentos). Ver
// SocioController::index() y la migración que habilita `unaccent` (R-1 del briefing).

namespace Tests\Feature\Socios;

use App\Models\Socio;
use App\Models\TipoSocio;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class SocioTest extends TestCase
{
    use RefreshDatabase;

    public function test_personal_puede_crear_un_socio(): void
    {
        $personal = User::factory()->create(['rol' => User::ROL_PERSONAL]);
        $tipoSocio = $this->crearTipoSocio();

        $respuesta = $this->actingAs($personal)->post(route('socios.socios.store'), [
            'nombre_principal' => 'Ana Pérez',
            'nombres_alternativos' => '',
            'fecha_alta' => '2026-01-01',
            'estado' => 'activo',
            'tipo_socio_id' => $tipoSocio->id,
        ]);

        $respuesta->assertRedirect(route('socios.socios.index'));
        $this->assertDatabaseHas('socios', ['nombre_principal' => 'Ana Pérez']);
    }

    public function test_la_busqueda_por_nombre_sin_tilde_encuentra_el_nombre_con_tilde(): void
    {
        $personal = User::factory()->create(['rol' => User::ROL_PERSONAL]);
        $tipoSocio = $this->crearTipoSocio();
        Socio::create([
            'nombre_principal' => 'María García',
            'fecha_alta' => '2020-01-01',
            'tipo_socio_id' => $tipoSocio->id,
        ]);
        Socio::create([
            'nombre_principal' => 'Juan Pérez',
            'fecha_alta' => '2020-01-01',
            'tipo_socio_id' => $tipoSocio->id,
        ]);

        $respuesta = $this->actingAs($personal)->get(route('socios.socios.index', ['busqueda' => 'Garcia']));

        $respuesta->assertOk();
        $respuesta->assertSee('María García');
        $respuesta->assertDontSee('Juan Pérez');
    }

    public function test_la_busqueda_encuentra_socios_por_nombre_alternativo(): void
    {
        $personal = User::factory()->create(['rol' => User::ROL_PERSONAL]);
        $tipoSocio = $this->crearTipoSocio();
        Socio::create([
            'nombre_principal' => 'María García',
            'nombres_alternativos' => ['Maria Garcia de los Santos'],
            'fecha_alta' => '2020-01-01',
            'tipo_socio_id' => $tipoSocio->id,
        ]);
        Socio::create([
            'nombre_principal' => 'Juan Pérez',
            'fecha_alta' => '2020-01-01',
            'tipo_socio_id' => $tipoSocio->id,
        ]);

        $respuesta = $this->actingAs($personal)->get(route('socios.socios.index', ['busqueda' => 'Garcia']));

        $respuesta->assertOk();
        $respuesta->assertSee('María García');
        $respuesta->assertDontSee('Juan Pérez');
    }

    private function crearTipoSocio(): TipoSocio
    {
        return TipoSocio::create([
            'nombre' => 'Estándar de prueba',
            'limite_prestamos_simultaneos' => 3,
            'sujeto_a_restriccion_automatica' => true,
        ]);
    }
}
