<?php

// Origen: Plan de Implementación v2, Módulo 5 — Renovaciones y reservas, criterios de aceptación
// de la renovación (RN-03, RN-19).

namespace Tests\Feature\Prestamos;

use App\Models\Ejemplar;
use App\Models\Libro;
use App\Models\PrestamoDomiciliario;
use App\Models\Renovacion;
use App\Models\Reserva;
use App\Models\Socio;
use App\Models\TipoSocio;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class RenovacionTest extends TestCase
{
    use RefreshDatabase;

    /**
     * Criterio literal: "La renovación de un préstamo con reservas pendientes es rechazada con el
     * mensaje 'El libro tiene una reserva pendiente de [nombre del socio].'"
     */
    public function test_la_renovacion_con_reserva_pendiente_es_rechazada_con_el_nombre_del_socio(): void
    {
        $admin = User::factory()->create(['rol' => User::ROL_ADMINISTRADOR]);
        $prestamo = $this->crearPrestamoActivo($admin);

        $otroSocio = Socio::create([
            'nombre_principal' => 'Lucía Fernández',
            'fecha_alta' => '2020-01-01',
            'tipo_socio_id' => $prestamo->socio->tipo_socio_id,
        ]);
        Reserva::create([
            'libro_id' => $prestamo->ejemplar->libro_id,
            'socio_id' => $otroSocio->id,
            'fecha_reserva' => now()->subDay()->toDateString(),
            'estado' => Reserva::ESTADO_PENDIENTE,
        ]);

        $respuesta = $this->actingAs($admin)->post(route('prestamos.renovar', $prestamo));

        $respuesta->assertSessionHasErrors(['renovacion' => 'El libro tiene una reserva pendiente de Lucía Fernández.']);
        $this->assertSame(0, Renovacion::where('prestamo_domiciliario_id', $prestamo->id)->count());
    }

    /**
     * Una reserva ya 'personal_alertado' también bloquea (RN-03 habla de "Pendiente o Personal
     * alertado", no solo del primer estado).
     */
    public function test_la_renovacion_con_reserva_personal_alertado_tambien_es_rechazada(): void
    {
        $admin = User::factory()->create(['rol' => User::ROL_ADMINISTRADOR]);
        $prestamo = $this->crearPrestamoActivo($admin);

        $otroSocio = Socio::create([
            'nombre_principal' => 'Marta Ibarra',
            'fecha_alta' => '2020-01-01',
            'tipo_socio_id' => $prestamo->socio->tipo_socio_id,
        ]);
        Reserva::create([
            'libro_id' => $prestamo->ejemplar->libro_id,
            'socio_id' => $otroSocio->id,
            'fecha_reserva' => now()->subDay()->toDateString(),
            'estado' => Reserva::ESTADO_PERSONAL_ALERTADO,
            'fecha_alerta_al_personal' => now(),
            'fecha_limite_retiro' => now()->addHours(48),
        ]);

        $this->actingAs($admin)->post(route('prestamos.renovar', $prestamo))
            ->assertSessionHasErrors('renovacion');
    }

    /**
     * Criterio literal: "La renovación de un préstamo sin reservas pendientes actualiza la fecha de
     * vencimiento y crea el registro de Renovación con la fecha anterior."
     */
    public function test_la_renovacion_sin_reservas_actualiza_vencimiento_y_registra_la_fecha_anterior(): void
    {
        $admin = User::factory()->create(['rol' => User::ROL_ADMINISTRADOR]);
        $prestamo = $this->crearPrestamoActivo($admin, fechaVencimiento: now()->addDays(3));
        $vencimientoAnterior = $prestamo->fecha_vencimiento->toDateString();

        $respuesta = $this->actingAs($admin)->post(route('prestamos.renovar', $prestamo));

        $respuesta->assertRedirect();
        $respuesta->assertSessionHasNoErrors();

        $prestamo->refresh();
        $plazoEsperado = now()->addDays(15)->toDateString();
        $this->assertSame($plazoEsperado, $prestamo->fecha_vencimiento->toDateString());
        // RN-19: el préstamo permanece en su mismo estado (no lo toca la renovación).
        $this->assertSame(PrestamoDomiciliario::ESTADO_ACTIVO, $prestamo->estado);

        $this->assertDatabaseHas('renovaciones', [
            'prestamo_domiciliario_id' => $prestamo->id,
            'fecha_vencimiento_anterior' => $vencimientoAnterior,
            'nueva_fecha_vencimiento' => $plazoEsperado,
        ]);
    }

    /**
     * Criterio literal (descripción del módulo): "Sin límite de renovaciones consecutivas — la
     * regla es la ausencia de reservas pendientes."
     */
    public function test_no_hay_limite_de_renovaciones_consecutivas(): void
    {
        $admin = User::factory()->create(['rol' => User::ROL_ADMINISTRADOR]);
        $prestamo = $this->crearPrestamoActivo($admin);

        $this->actingAs($admin)->post(route('prestamos.renovar', $prestamo))->assertSessionHasNoErrors();
        $this->actingAs($admin)->post(route('prestamos.renovar', $prestamo))->assertSessionHasNoErrors();
        $this->actingAs($admin)->post(route('prestamos.renovar', $prestamo))->assertSessionHasNoErrors();

        $this->assertSame(3, Renovacion::where('prestamo_domiciliario_id', $prestamo->id)->count());
    }

    private function crearPrestamoActivo(User $registrador, $fechaVencimiento = null): PrestamoDomiciliario
    {
        $socio = Socio::create([
            'nombre_principal' => 'Socio de prueba '.uniqid(),
            'fecha_alta' => '2020-01-01',
            'tipo_socio_id' => TipoSocio::create([
                'nombre' => 'Estándar de prueba '.uniqid(),
                'limite_prestamos_simultaneos' => 3,
                'sujeto_a_restriccion_automatica' => true,
            ])->id,
        ]);

        $libro = Libro::create(['titulo' => 'Libro de prueba '.uniqid()]);
        $ejemplar = $libro->ejemplares()->create([
            'modalidad_acceso' => Ejemplar::MODALIDAD_LIBRE_CIRCULACION,
            'fecha_ingreso' => '2020-01-01',
            'origen' => Ejemplar::ORIGEN_COMPRA,
        ]);

        return PrestamoDomiciliario::create([
            'ejemplar_id' => $ejemplar->id,
            'socio_id' => $socio->id,
            'fecha_registro' => now(),
            'fecha_prestamo' => now()->subDays(5)->toDateString(),
            'fecha_vencimiento' => ($fechaVencimiento ?? now()->addDays(10))->toDateString(),
            'estado' => PrestamoDomiciliario::ESTADO_ACTIVO,
            'registrado_por' => $registrador->id,
        ]);
    }
}
