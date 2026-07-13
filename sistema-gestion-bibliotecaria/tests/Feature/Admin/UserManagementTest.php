<?php

// Origen: Plan de Implementación v2, Módulo 1 — CRUD de usuarios por el Administrador.

namespace Tests\Feature\Admin;

use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class UserManagementTest extends TestCase
{
    use RefreshDatabase;

    public function test_un_administrador_puede_crear_un_usuario_y_asignarle_rol(): void
    {
        $admin = User::factory()->create(['rol' => User::ROL_ADMINISTRADOR]);

        $respuesta = $this->actingAs($admin)->post('/admin/users', [
            'name' => 'Nuevo Voluntario',
            'email' => 'nuevo.voluntario@biblioteca.test',
            'password' => 'contrasena-segura-123',
            'password_confirmation' => 'contrasena-segura-123',
            'rol' => User::ROL_VOLUNTARIO,
        ]);

        $respuesta->assertRedirect(route('admin.users.index'));
        $this->assertDatabaseHas('users', [
            'email' => 'nuevo.voluntario@biblioteca.test',
            'rol' => User::ROL_VOLUNTARIO,
        ]);
    }

    public function test_inactivar_un_usuario_no_lo_elimina(): void
    {
        $admin = User::factory()->create(['rol' => User::ROL_ADMINISTRADOR]);
        $usuario = User::factory()->create(['estado' => 'activo']);

        $this->actingAs($admin)->patch(route('admin.users.inactivar', $usuario));

        $this->assertDatabaseHas('users', ['id' => $usuario->id, 'estado' => 'inactivo']);
    }
}
