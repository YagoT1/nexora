<?php

// Origen: Plan de Implementación v2, Módulo 4 — Préstamos y devoluciones, criterios de aceptación
// de la devolución (RN-12, RN-18, RN-07, alerta de reserva pendiente).

namespace Tests\Feature\Prestamos;

use App\Models\Ejemplar;
use App\Models\HistorialAtraso;
use App\Models\Libro;
use App\Models\ParametroConfiguracion;
use App\Models\PrestamoDomiciliario;
use App\Models\Reserva;
use App\Models\RestriccionSocio;
use App\Models\Socio;
use App\Models\TipoSocio;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class DevolucionTest extends TestCase
{
    use RefreshDatabase;

    /**
     * Criterio: "La devolución puede registrarse sin identificar quién trae el libro" (RN-12).
     * El formulario de confirmación no tiene ningún campo de socio — se comprueba posteando solo
     * fecha_devolucion_efectiva.
     */
    public function test_la_devolucion_puede_registrarse_sin_identificar_quien_trae_el_libro(): void
    {
        $admin = User::factory()->create(['rol' => User::ROL_ADMINISTRADOR]);
        $prestamo = $this->crearPrestamoActivo($admin);

        $respuesta = $this->actingAs($admin)->post(route('prestamos.devolucion.store', $prestamo), [
            'fecha_devolucion_efectiva' => now()->toDateString(),
        ]);

        $respuesta->assertRedirect(route('prestamos.devolucion.buscar'));
        $this->assertSame(PrestamoDomiciliario::ESTADO_DEVUELTO, $prestamo->fresh()->estado);
    }

    public function test_la_devolucion_tardia_con_3_dias_de_atraso_genera_una_restriccion_de_3_dias(): void
    {
        $admin = User::factory()->create(['rol' => User::ROL_ADMINISTRADOR]);
        $prestamo = $this->crearPrestamoActivo($admin, fechaVencimiento: now()->subDays(3));

        $fechaDevolucion = now()->toDateString();
        $this->actingAs($admin)->post(route('prestamos.devolucion.store', $prestamo), [
            'fecha_devolucion_efectiva' => $fechaDevolucion,
        ])->assertRedirect();

        $this->assertDatabaseHas('historial_atrasos', [
            'prestamo_domiciliario_id' => $prestamo->id,
            'dias_atraso' => 3,
            'restriccion_generada' => true,
        ]);

        $restriccion = RestriccionSocio::where('prestamo_domiciliario_id', $prestamo->id)->firstOrFail();
        $this->assertSame($fechaDevolucion, $restriccion->fecha_inicio->toDateString());
        $this->assertSame(now()->addDays(3)->toDateString(), $restriccion->fecha_fin->toDateString());
    }

    /**
     * RN-07: un socio Honorario no recibe restricción automática, aunque el atraso se registre
     * igual en el historial.
     */
    public function test_un_socio_honorario_con_devolucion_tardia_no_recibe_restriccion_automatica(): void
    {
        $admin = User::factory()->create(['rol' => User::ROL_ADMINISTRADOR]);
        $honorario = TipoSocio::create([
            'nombre' => 'Honorario',
            'limite_prestamos_simultaneos' => 5,
            'sujeto_a_restriccion_automatica' => false,
        ]);
        $socio = Socio::create([
            'nombre_principal' => 'Socio honorario',
            'fecha_alta' => '2020-01-01',
            'tipo_socio_id' => $honorario->id,
        ]);
        $prestamo = $this->crearPrestamoActivo($admin, socio: $socio, fechaVencimiento: now()->subDays(3));

        $this->actingAs($admin)->post(route('prestamos.devolucion.store', $prestamo), [
            'fecha_devolucion_efectiva' => now()->toDateString(),
        ])->assertRedirect();

        $this->assertDatabaseHas('historial_atrasos', [
            'prestamo_domiciliario_id' => $prestamo->id,
            'restriccion_generada' => false,
        ]);
        $this->assertSame(0, RestriccionSocio::where('socio_id', $socio->id)->count());
    }

    public function test_la_restriccion_automatica_respeta_el_tope_maximo_configurado(): void
    {
        $admin = User::factory()->create(['rol' => User::ROL_ADMINISTRADOR]);
        ParametroConfiguracion::firstOrCreate(
            ['clave' => ParametroConfiguracion::TOPE_MAXIMO_RESTRICCION_DIAS],
            ['valor' => '30']
        );
        $prestamo = $this->crearPrestamoActivo($admin, fechaVencimiento: now()->subDays(60));

        $fechaDevolucion = now()->toDateString();
        $this->actingAs($admin)->post(route('prestamos.devolucion.store', $prestamo), [
            'fecha_devolucion_efectiva' => $fechaDevolucion,
        ])->assertRedirect();

        $restriccion = RestriccionSocio::where('prestamo_domiciliario_id', $prestamo->id)->firstOrFail();
        $this->assertSame(60, $restriccion->dias_atraso_origen);
        $this->assertSame(now()->addDays(30)->toDateString(), $restriccion->fecha_fin->toDateString());
    }

    public function test_una_devolucion_a_tiempo_no_genera_historial_de_atraso(): void
    {
        $admin = User::factory()->create(['rol' => User::ROL_ADMINISTRADOR]);
        $prestamo = $this->crearPrestamoActivo($admin, fechaVencimiento: now()->addDays(5));

        $this->actingAs($admin)->post(route('prestamos.devolucion.store', $prestamo), [
            'fecha_devolucion_efectiva' => now()->toDateString(),
        ])->assertRedirect();

        $this->assertSame(0, HistorialAtraso::where('prestamo_domiciliario_id', $prestamo->id)->count());
    }

    /**
     * Criterio: "La devolución de un libro con reserva pendiente activa la alerta de 'avisar al
     * socio' ... dentro del ciclo de la misma request."
     */
    public function test_la_devolucion_de_un_libro_con_reserva_pendiente_marca_la_reserva_como_personal_alertado(): void
    {
        $admin = User::factory()->create(['rol' => User::ROL_ADMINISTRADOR]);
        $prestamo = $this->crearPrestamoActivo($admin);
        $otroSocio = Socio::create([
            'nombre_principal' => 'Socio en espera',
            'fecha_alta' => '2020-01-01',
            'tipo_socio_id' => $prestamo->socio->tipo_socio_id,
        ]);
        $reserva = Reserva::create([
            'libro_id' => $prestamo->ejemplar->libro_id,
            'socio_id' => $otroSocio->id,
            'fecha_reserva' => now()->subDays(1)->toDateString(),
            'estado' => 'pendiente',
        ]);

        $respuesta = $this->actingAs($admin)->post(route('prestamos.devolucion.store', $prestamo), [
            'fecha_devolucion_efectiva' => now()->toDateString(),
        ]);

        $respuesta->assertRedirect(route('prestamos.devolucion.buscar'));
        $respuesta->assertSessionHas('alertas');
        $reserva->refresh();
        $this->assertSame('personal_alertado', $reserva->estado);
        $this->assertNotNull($reserva->fecha_alerta_al_personal);
        $this->assertSame($prestamo->ejemplar_id, $reserva->ejemplar_asignado_id);
    }

    public function test_la_condicion_fisica_informada_en_la_devolucion_se_guarda_en_el_ejemplar(): void
    {
        $admin = User::factory()->create(['rol' => User::ROL_ADMINISTRADOR]);
        $prestamo = $this->crearPrestamoActivo($admin);

        $this->actingAs($admin)->post(route('prestamos.devolucion.store', $prestamo), [
            'fecha_devolucion_efectiva' => now()->toDateString(),
            'condicion_fisica' => 'Tapa despegada.',
        ])->assertRedirect();

        $this->assertSame('Tapa despegada.', $prestamo->ejemplar->fresh()->condicion_fisica);
    }

    private function crearPrestamoActivo(User $registrador, ?Socio $socio = null, $fechaVencimiento = null): PrestamoDomiciliario
    {
        $socio ??= Socio::create([
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
            'fecha_prestamo' => now()->subDays(20)->toDateString(),
            'fecha_vencimiento' => ($fechaVencimiento ?? now()->addDays(15))->toDateString(),
            'estado' => PrestamoDomiciliario::ESTADO_ACTIVO,
            'registrado_por' => $registrador->id,
        ]);
    }
}
