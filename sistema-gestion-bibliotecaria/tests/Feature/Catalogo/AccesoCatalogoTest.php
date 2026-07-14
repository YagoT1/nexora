<?php

// Origen: Modelo de Dominio v2, 6.1 (tabla de permisos): "Gestionar catálogo y ejemplares" es
// competencia de Administrador y Personal, no de Voluntario. Mismo patrón de prueba que
// tests/Feature/RoleAuthorizationTest.php (Módulo 1), aplicado a las rutas de catalogo.*.

namespace Tests\Feature\Catalogo;

use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class AccesoCatalogoTest extends TestCase
{
    use RefreshDatabase;

    public function test_un_voluntario_no_puede_acceder_al_listado_de_libros(): void
    {
        $voluntario = User::factory()->create(['rol' => User::ROL_VOLUNTARIO]);

        $respuesta = $this->actingAs($voluntario)->get(route('catalogo.libros.index'));

        $respuesta->assertForbidden();
    }

    public function test_un_usuario_personal_si_puede_acceder_al_listado_de_libros(): void
    {
        $personal = User::factory()->create(['rol' => User::ROL_PERSONAL]);

        $respuesta = $this->actingAs($personal)->get(route('catalogo.libros.index'));

        $respuesta->assertOk();
    }

    public function test_un_administrador_si_puede_acceder_al_listado_de_libros(): void
    {
        $admin = User::factory()->create(['rol' => User::ROL_ADMINISTRADOR]);

        $respuesta = $this->actingAs($admin)->get(route('catalogo.libros.index'));

        $respuesta->assertOk();
    }

    public function test_un_visitante_no_autenticado_es_redirigido_al_login(): void
    {
        $respuesta = $this->get(route('catalogo.libros.index'));

        $respuesta->assertRedirect('/login');
    }
}
