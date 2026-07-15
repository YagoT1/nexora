<?php

// Origen: Modelo de Dominio v2, 6.1 (tabla de permisos) — préstamos y devoluciones son competencia
// de Administrador y Personal, no de Voluntario. Mismo patrón que AccesoCatalogoTest/AccesoSociosTest.

namespace Tests\Feature\Prestamos;

use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class AccesoPrestamosTest extends TestCase
{
    use RefreshDatabase;

    public function test_un_voluntario_no_puede_acceder_al_registro_de_prestamos(): void
    {
        $voluntario = User::factory()->create(['rol' => User::ROL_VOLUNTARIO]);

        $respuesta = $this->actingAs($voluntario)->get(route('prestamos.create'));

        $respuesta->assertForbidden();
    }

    public function test_un_usuario_personal_si_puede_acceder_al_registro_de_prestamos(): void
    {
        $personal = User::factory()->create(['rol' => User::ROL_PERSONAL]);

        $respuesta = $this->actingAs($personal)->get(route('prestamos.create'));

        $respuesta->assertOk();
    }

    public function test_un_visitante_no_autenticado_es_redirigido_al_login(): void
    {
        $respuesta = $this->get(route('prestamos.create'));

        $respuesta->assertRedirect('/login');
    }

    public function test_un_voluntario_no_puede_acceder_a_la_busqueda_de_devolucion(): void
    {
        $voluntario = User::factory()->create(['rol' => User::ROL_VOLUNTARIO]);

        $respuesta = $this->actingAs($voluntario)->get(route('prestamos.devolucion.buscar'));

        $respuesta->assertForbidden();
    }
}
