<?php

// Origen: Plan de Implementación v2, Módulo 2 — Catálogo, criterio de aceptación 7 y RN-21
// (Modelo de Dominio v2): "Al intentar cambiar la modalidad de acceso del único ejemplar disponible
// de un Libro con reservas Pendientes a Solo sala, el sistema muestra una alerta...". Paso 7 del
// briefing. RN-21 no exige bloquear el cambio ni cancelar la reserva automáticamente — solo alertar
// al personal para que la gestione manualmente (ver EjemplarController::update()).

namespace Tests\Feature\Catalogo;

use App\Models\Ejemplar;
use App\Models\Libro;
use App\Models\Reserva;
use App\Models\Socio;
use App\Models\TipoSocio;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class Rn21ModalidadTest extends TestCase
{
    use RefreshDatabase;

    public function test_alerta_rn21_al_dejar_sin_ejemplares_disponibles_un_libro_con_reserva_pendiente(): void
    {
        $admin = User::factory()->create(['rol' => User::ROL_ADMINISTRADOR]);
        $libro = Libro::create(['titulo' => 'Libro con reserva pendiente']);
        $unicoEjemplar = Ejemplar::create([
            'libro_id' => $libro->id,
            'modalidad_acceso' => Ejemplar::MODALIDAD_LIBRE_CIRCULACION,
            'fecha_ingreso' => '2020-01-01',
            'origen' => Ejemplar::ORIGEN_COMPRA,
        ]);
        $this->crearReservaPendiente($libro);

        $respuesta = $this->actingAs($admin)->put(
            route('catalogo.libros.ejemplares.update', [$libro, $unicoEjemplar]),
            [
                'modalidad_acceso' => Ejemplar::MODALIDAD_SOLO_SALA,
                'fecha_ingreso' => '2020-01-01',
                'origen' => Ejemplar::ORIGEN_COMPRA,
            ]
        );

        $respuesta->assertRedirect(route('catalogo.libros.show', $libro));
        $respuesta->assertSessionHas('status', function ($mensaje) {
            return str_contains($mensaje, 'RN-21');
        });
    }

    public function test_no_hay_alerta_rn21_si_otro_ejemplar_del_mismo_libro_sigue_disponible(): void
    {
        $admin = User::factory()->create(['rol' => User::ROL_ADMINISTRADOR]);
        $libro = Libro::create(['titulo' => 'Libro con dos ejemplares']);
        $ejemplarQueCambia = Ejemplar::create([
            'libro_id' => $libro->id,
            'modalidad_acceso' => Ejemplar::MODALIDAD_LIBRE_CIRCULACION,
            'fecha_ingreso' => '2020-01-01',
            'origen' => Ejemplar::ORIGEN_COMPRA,
        ]);
        Ejemplar::create([
            'libro_id' => $libro->id,
            'modalidad_acceso' => Ejemplar::MODALIDAD_LIBRE_CIRCULACION,
            'fecha_ingreso' => '2021-01-01',
            'origen' => Ejemplar::ORIGEN_COMPRA,
        ]);
        $this->crearReservaPendiente($libro);

        $respuesta = $this->actingAs($admin)->put(
            route('catalogo.libros.ejemplares.update', [$libro, $ejemplarQueCambia]),
            [
                'modalidad_acceso' => Ejemplar::MODALIDAD_SOLO_SALA,
                'fecha_ingreso' => '2020-01-01',
                'origen' => Ejemplar::ORIGEN_COMPRA,
            ]
        );

        $respuesta->assertRedirect(route('catalogo.libros.show', $libro));
        $respuesta->assertSessionHas('status', function ($mensaje) {
            return ! str_contains($mensaje, 'RN-21');
        });
    }

    public function test_no_hay_alerta_rn21_si_el_libro_no_tiene_reservas_pendientes(): void
    {
        $admin = User::factory()->create(['rol' => User::ROL_ADMINISTRADOR]);
        $libro = Libro::create(['titulo' => 'Libro sin reservas']);
        $unicoEjemplar = Ejemplar::create([
            'libro_id' => $libro->id,
            'modalidad_acceso' => Ejemplar::MODALIDAD_LIBRE_CIRCULACION,
            'fecha_ingreso' => '2020-01-01',
            'origen' => Ejemplar::ORIGEN_COMPRA,
        ]);

        $respuesta = $this->actingAs($admin)->put(
            route('catalogo.libros.ejemplares.update', [$libro, $unicoEjemplar]),
            [
                'modalidad_acceso' => Ejemplar::MODALIDAD_SOLO_SALA,
                'fecha_ingreso' => '2020-01-01',
                'origen' => Ejemplar::ORIGEN_COMPRA,
            ]
        );

        $respuesta->assertRedirect(route('catalogo.libros.show', $libro));
        $respuesta->assertSessionHas('status', function ($mensaje) {
            return ! str_contains($mensaje, 'RN-21');
        });
    }

    public function test_no_hay_alerta_rn21_si_se_edita_el_ejemplar_sin_cambiar_la_modalidad(): void
    {
        $admin = User::factory()->create(['rol' => User::ROL_ADMINISTRADOR]);
        $libro = Libro::create(['titulo' => 'Libro con edición sin cambio de modalidad']);
        $unicoEjemplar = Ejemplar::create([
            'libro_id' => $libro->id,
            'modalidad_acceso' => Ejemplar::MODALIDAD_LIBRE_CIRCULACION,
            'fecha_ingreso' => '2020-01-01',
            'origen' => Ejemplar::ORIGEN_COMPRA,
        ]);
        $this->crearReservaPendiente($libro);

        $respuesta = $this->actingAs($admin)->put(
            route('catalogo.libros.ejemplares.update', [$libro, $unicoEjemplar]),
            [
                'modalidad_acceso' => Ejemplar::MODALIDAD_LIBRE_CIRCULACION,
                'condicion_fisica' => 'Buen estado',
                'fecha_ingreso' => '2020-01-01',
                'origen' => Ejemplar::ORIGEN_COMPRA,
            ]
        );

        $respuesta->assertRedirect(route('catalogo.libros.show', $libro));
        $respuesta->assertSessionHas('status', function ($mensaje) {
            return ! str_contains($mensaje, 'RN-21');
        });
    }

    private function crearReservaPendiente(Libro $libro): Reserva
    {
        $tipoSocio = TipoSocio::create([
            'nombre' => 'Estándar de prueba RN-21',
            'limite_prestamos_simultaneos' => 3,
            'sujeto_a_restriccion_automatica' => true,
        ]);
        $socio = Socio::create([
            'nombre_principal' => 'Socio con reserva pendiente',
            'fecha_alta' => '2020-01-01',
            'tipo_socio_id' => $tipoSocio->id,
        ]);

        return Reserva::create([
            'libro_id' => $libro->id,
            'socio_id' => $socio->id,
            'fecha_reserva' => now()->toDateString(),
            'estado' => 'pendiente',
        ]);
    }
}
