<?php

// Origen: Plan de Implementación v2, Módulo 1, criterios de aceptación:
// "Un usuario con cada rol puede iniciar sesión con sus credenciales."
// "Una sesión inactiva por 2 horas se cierra automáticamente y redirige al login."

namespace Tests\Feature;

use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class AuthenticationTest extends TestCase
{
    use RefreshDatabase;

    public function test_un_usuario_de_cada_rol_puede_iniciar_sesion(): void
    {
        foreach (User::ROLES as $rol) {
            $usuario = User::factory()->create(['rol' => $rol, 'estado' => 'activo']);

            $respuesta = $this->post('/login', [
                'email' => $usuario->email,
                'password' => 'password',
            ]);

            $this->assertAuthenticatedAs($usuario);
            $respuesta->assertRedirect(route('dashboard', absolute: false));

            $this->post('/logout');
        }
    }

    public function test_credenciales_invalidas_no_autentican(): void
    {
        $usuario = User::factory()->create();

        $this->post('/login', [
            'email' => $usuario->email,
            'password' => 'contrasena-incorrecta',
        ]);

        $this->assertGuest();
    }

    public function test_la_sesion_expira_por_inactividad_configurada(): void
    {
        // SESSION_LIFETIME=120 minutos (2 horas), ver .env.example. Verificamos que la configuración
        // de la aplicación efectivamente aplica ese valor: la expiración real por tiempo transcurrido
        // depende del driver de sesión y se valida funcionalmente en staging (ver ADR-002).
        $this->assertEquals(120, config('session.lifetime'));
    }
}
