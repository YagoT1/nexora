<?php

// Origen: Plan de Implementación v2, Módulo 1, criterio de aceptación:
// "Un voluntario que intenta acceder a una ruta de administración recibe un error
// de autorización, no el contenido."

namespace Tests\Feature;

use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class RoleAuthorizationTest extends TestCase
{
    use RefreshDatabase;

    public function test_un_voluntario_no_puede_acceder_al_panel_de_administracion(): void
    {
        $voluntario = User::factory()->create(['rol' => User::ROL_VOLUNTARIO]);

        $respuesta = $this->actingAs($voluntario)->get('/admin/users');

        $respuesta->assertForbidden();
    }

    public function test_un_usuario_personal_no_puede_acceder_al_panel_de_administracion(): void
    {
        $personal = User::factory()->create(['rol' => User::ROL_PERSONAL]);

        $respuesta = $this->actingAs($personal)->get('/admin/users');

        $respuesta->assertForbidden();
    }

    public function test_un_administrador_si_puede_acceder_al_panel_de_administracion(): void
    {
        $admin = User::factory()->create(['rol' => User::ROL_ADMINISTRADOR]);

        $respuesta = $this->actingAs($admin)->get('/admin/users');

        $respuesta->assertOk();
    }

    public function test_un_usuario_inactivo_no_puede_acceder_aunque_sea_administrador(): void
    {
        $admin = User::factory()->create(['rol' => User::ROL_ADMINISTRADOR, 'estado' => 'inactivo']);

        $respuesta = $this->actingAs($admin)->get('/admin/users');

        $respuesta->assertForbidden();
    }

    public function test_un_visitante_no_autenticado_es_redirigido_al_login(): void
    {
        $respuesta = $this->get('/admin/users');

        $respuesta->assertRedirect('/login');
    }
}
