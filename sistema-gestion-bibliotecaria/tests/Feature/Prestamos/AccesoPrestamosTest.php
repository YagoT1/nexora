<?php

// Origen: Modelo de Dominio v2, 6.1 (tabla de permisos) — préstamos y devoluciones son competencia
// de Administrador y Personal, no de Voluntario. Mismo patrón que AccesoCatalogoTest/AccesoSociosTest.
// Módulo 5: las rutas de renovación y reserva heredan el mismo middleware de rol que sus grupos de
// rutas ('prestamos.*' y 'catalogo.*' respectivamente) — no se agregó ningún middleware nuevo.

namespace Tests\Feature\Prestamos;

use App\Models\Libro;
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

    /**
     * Origen: Módulo 5, Paso 6. La ruta de alta de reserva vive en el grupo 'catalogo.*', mismo
     * middleware de rol que el resto de Catálogo — no se creó ningún middleware nuevo para Módulo 5.
     */
    public function test_un_voluntario_no_puede_acceder_al_alta_de_reserva(): void
    {
        $voluntario = User::factory()->create(['rol' => User::ROL_VOLUNTARIO]);
        $libro = Libro::create(['titulo' => 'Libro de prueba de acceso']);

        $respuesta = $this->actingAs($voluntario)->get(route('catalogo.libros.reservas.create', $libro));

        $respuesta->assertForbidden();
    }

    public function test_un_usuario_personal_si_puede_acceder_al_alta_de_reserva(): void
    {
        $personal = User::factory()->create(['rol' => User::ROL_PERSONAL]);
        $libro = Libro::create(['titulo' => 'Libro de prueba de acceso']);

        $respuesta = $this->actingAs($personal)->get(route('catalogo.libros.reservas.create', $libro));

        $respuesta->assertOk();
    }
}
