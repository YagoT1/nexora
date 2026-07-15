<?php

// Origen: Plan de Implementación v2, Módulo 5 — Renovaciones y reservas, criterios de aceptación
// de la reserva (asignación automática RN-05, "un socio no puede tener dos reservas activas para
// el mismo Libro").

namespace Tests\Feature\Prestamos;

use App\Models\Ejemplar;
use App\Models\Libro;
use App\Models\PrestamoDomiciliario;
use App\Models\Reserva;
use App\Models\Socio;
use App\Models\TipoSocio;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class ReservaTest extends TestCase
{
    use RefreshDatabase;

    public function test_un_socio_puede_reservar_un_libro(): void
    {
        $admin = User::factory()->create(['rol' => User::ROL_ADMINISTRADOR]);
        $libro = Libro::create(['titulo' => 'Libro reservable']);
        $socio = $this->crearSocio();

        $respuesta = $this->actingAs($admin)->post(route('catalogo.libros.reservas.store', $libro), [
            'socio_id' => $socio->id,
        ]);

        $respuesta->assertRedirect(route('catalogo.libros.show', $libro));
        $this->assertDatabaseHas('reservas', [
            'libro_id' => $libro->id,
            'socio_id' => $socio->id,
            'estado' => Reserva::ESTADO_PENDIENTE,
        ]);
    }

    /**
     * Criterio literal: "Un socio no puede tener dos reservas activas para el mismo Libro."
     */
    public function test_un_socio_no_puede_tener_dos_reservas_activas_para_el_mismo_libro(): void
    {
        $admin = User::factory()->create(['rol' => User::ROL_ADMINISTRADOR]);
        $libro = Libro::create(['titulo' => 'Libro muy pedido']);
        $socio = $this->crearSocio();

        Reserva::create([
            'libro_id' => $libro->id,
            'socio_id' => $socio->id,
            'fecha_reserva' => now()->toDateString(),
            'estado' => Reserva::ESTADO_PENDIENTE,
        ]);

        $respuesta = $this->actingAs($admin)->post(route('catalogo.libros.reservas.store', $libro), [
            'socio_id' => $socio->id,
        ]);

        $respuesta->assertSessionHasErrors('socio_id');
        $this->assertSame(1, Reserva::where('libro_id', $libro->id)->where('socio_id', $socio->id)->count());
    }

    /**
     * Una reserva ya resuelta (retirada, vencida o cancelada) no cuenta como "activa": el socio
     * puede volver a reservar el mismo título.
     */
    public function test_un_socio_puede_volver_a_reservar_tras_retirar_una_reserva_anterior(): void
    {
        $admin = User::factory()->create(['rol' => User::ROL_ADMINISTRADOR]);
        $libro = Libro::create(['titulo' => 'Libro con historial de reservas']);
        $socio = $this->crearSocio();

        Reserva::create([
            'libro_id' => $libro->id,
            'socio_id' => $socio->id,
            'fecha_reserva' => now()->subDays(30)->toDateString(),
            'estado' => Reserva::ESTADO_RETIRADA,
        ]);

        $this->actingAs($admin)->post(route('catalogo.libros.reservas.store', $libro), [
            'socio_id' => $socio->id,
        ])->assertSessionHasNoErrors();

        $this->assertSame(2, Reserva::where('libro_id', $libro->id)->where('socio_id', $socio->id)->count());
    }

    /**
     * Criterio literal: "Cuando el ejemplar de un Libro reservado es devuelto, la reserva más
     * antigua del Libro pasa a 'Personal alertado' y aparece en el panel del mostrador. El panel
     * muestra correctamente la fecha límite de retiro del ejemplar apartado."
     *
     * Cubre el mismo comportamiento que DevolucionTest (Módulo 4), pero verificando además que la
     * pantalla de devolución (que hace de "panel del mostrador", ver PrestamoController::
     * buscarDevolucion()) efectivamente la muestra con su fecha límite de retiro calculada.
     */
    public function test_la_reserva_asignada_aparece_en_el_panel_con_su_fecha_limite_de_retiro(): void
    {
        $admin = User::factory()->create(['rol' => User::ROL_ADMINISTRADOR]);
        $prestamo = $this->crearPrestamoActivo($admin);
        $socioReservante = $this->crearSocio('Lucía Fernández');

        Reserva::create([
            'libro_id' => $prestamo->ejemplar->libro_id,
            'socio_id' => $socioReservante->id,
            'fecha_reserva' => now()->subDay()->toDateString(),
            'estado' => Reserva::ESTADO_PENDIENTE,
        ]);

        $this->actingAs($admin)->post(route('prestamos.devolucion.store', $prestamo), [
            'fecha_devolucion_efectiva' => now()->toDateString(),
        ])->assertRedirect();

        $reserva = Reserva::where('libro_id', $prestamo->ejemplar->libro_id)->firstOrFail();
        $this->assertSame(Reserva::ESTADO_PERSONAL_ALERTADO, $reserva->estado);
        $this->assertNotNull($reserva->fecha_limite_retiro);

        $respuesta = $this->actingAs($admin)->get(route('prestamos.devolucion.buscar'));
        $respuesta->assertSee($socioReservante->nombre_principal);
        $respuesta->assertSee($reserva->fecha_limite_retiro->format('d/m/Y'));
    }

    private function crearSocio(?string $nombre = null): Socio
    {
        return Socio::create([
            'nombre_principal' => $nombre ?? 'Socio de prueba '.uniqid(),
            'fecha_alta' => '2020-01-01',
            'tipo_socio_id' => TipoSocio::create([
                'nombre' => 'Estándar de prueba '.uniqid(),
                'limite_prestamos_simultaneos' => 3,
                'sujeto_a_restriccion_automatica' => true,
            ])->id,
        ]);
    }

    private function crearPrestamoActivo(User $registrador): PrestamoDomiciliario
    {
        $socio = $this->crearSocio();
        $libro = Libro::create(['titulo' => 'Libro reservado y prestado']);
        $ejemplar = $libro->ejemplares()->create([
            'modalidad_acceso' => Ejemplar::MODALIDAD_LIBRE_CIRCULACION,
            'fecha_ingreso' => '2020-01-01',
            'origen' => Ejemplar::ORIGEN_COMPRA,
        ]);

        return PrestamoDomiciliario::create([
            'ejemplar_id' => $ejemplar->id,
            'socio_id' => $socio->id,
            'fecha_registro' => now(),
            'fecha_prestamo' => now()->subDays(20)->toDateString(),
            'fecha_vencimiento' => now()->addDays(15)->toDateString(),
            'estado' => PrestamoDomiciliario::ESTADO_ACTIVO,
            'registrado_por' => $registrador->id,
        ]);
    }
}
