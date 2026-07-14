<?php

// Origen: Modelo de Dominio v2, 6.1 (tabla de permisos): "Gestionar socios" es competencia de
// Administrador y Personal, no de Voluntario. Mismo patrón que
// tests/Feature/Catalogo/AccesoCatalogoTest.php (Módulo 2), aplicado a las rutas de socios.*.

namespace Tests\Feature\Socios;

use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class AccesoSociosTest extends TestCase
{
    use RefreshDatabase;

    public function test_un_voluntario_no_puede_acceder_al_listado_de_socios(): void
    {
        $voluntario = User::factory()->create(['rol' => User::ROL_VOLUNTARIO]);

        $respuesta = $this->actingAs($voluntario)->get(route('socios.socios.index'));

        $respuesta->assertForbidden();
    }

    public function test_un_usuario_personal_si_puede_acceder_al_listado_de_socios(): void
    {
        $personal = User::factory()->create(['rol' => User::ROL_PERSONAL]);

        $respuesta = $this->actingAs($personal)->get(route('socios.socios.index'));

        $respuesta->assertOk();
    }

    public function test_un_administrador_si_puede_acceder_al_listado_de_socios(): void
    {
        $admin = User::factory()->create(['rol' => User::ROL_ADMINISTRADOR]);

        $respuesta = $this->actingAs($admin)->get(route('socios.socios.index'));

        $respuesta->assertOk();
    }

    public function test_un_visitante_no_autenticado_es_redirigido_al_login(): void
    {
        $respuesta = $this->get(route('socios.socios.index'));

        $respuesta->assertRedirect('/login');
    }
}
