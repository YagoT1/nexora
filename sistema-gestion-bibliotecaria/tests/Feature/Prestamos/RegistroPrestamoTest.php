<?php

// Origen: Plan de Implementación v2, Módulo 4 — Préstamos y devoluciones, criterios de aceptación
// del registro de préstamo. Ver Fase 6 - Development/BRIEFING-MODULO-4-PRESTAMOS.md.

namespace Tests\Feature\Prestamos;

use App\Models\Ejemplar;
use App\Models\ExcepcionAutorizada;
use App\Models\Libro;
use App\Models\PrestamoDomiciliario;
use App\Models\RestriccionSocio;
use App\Models\Socio;
use App\Models\TipoSocio;
use App\Models\User;
use Illuminate\Database\QueryException;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class RegistroPrestamoTest extends TestCase
{
    use RefreshDatabase;

    /**
     * Criterio: "El préstamo de un ejemplar con préstamo activo es rechazado por la base de datos,
     * no solo por el código de aplicación." Se prueba insertando el segundo préstamo activo
     * directamente vía Eloquent (sin pasar por PrestamoController::store(), que lo hubiera
     * rechazado antes) para demostrar que el índice único parcial (DA-09 Nivel 1) es quien
     * realmente lo impide, independientemente de cualquier verificación de aplicación.
     */
    public function test_la_base_de_datos_rechaza_un_segundo_prestamo_activo_para_el_mismo_ejemplar(): void
    {
        $admin = User::factory()->create(['rol' => User::ROL_ADMINISTRADOR]);
        $ejemplar = $this->crearEjemplarDisponible();
        $socio1 = $this->crearSocio();
        $socio2 = $this->crearSocio();

        PrestamoDomiciliario::create([
            'ejemplar_id' => $ejemplar->id,
            'socio_id' => $socio1->id,
            'fecha_registro' => now(),
            'fecha_prestamo' => now()->toDateString(),
            'fecha_vencimiento' => now()->addDays(15)->toDateString(),
            'estado' => PrestamoDomiciliario::ESTADO_ACTIVO,
            'registrado_por' => $admin->id,
        ]);

        $this->expectException(QueryException::class);

        // Se salta deliberadamente cualquier chequeo de aplicación (no se llama a
        // tieneMovimientoActivo() antes) para demostrar que, aun así, la base de datos rechaza el
        // segundo movimiento activo — el índice único parcial no depende del código de aplicación.
        PrestamoDomiciliario::create([
            'ejemplar_id' => $ejemplar->id,
            'socio_id' => $socio2->id,
            'fecha_registro' => now(),
            'fecha_prestamo' => now()->toDateString(),
            'fecha_vencimiento' => now()->addDays(15)->toDateString(),
            'estado' => PrestamoDomiciliario::ESTADO_ACTIVO,
            'registrado_por' => $admin->id,
        ]);
    }

    public function test_el_controlador_rechaza_con_un_mensaje_claro_un_ejemplar_ya_prestado(): void
    {
        $admin = User::factory()->create(['rol' => User::ROL_ADMINISTRADOR]);
        $ejemplar = $this->crearEjemplarDisponible();
        $socio1 = $this->crearSocio();
        $socio2 = $this->crearSocio();

        PrestamoDomiciliario::create([
            'ejemplar_id' => $ejemplar->id,
            'socio_id' => $socio1->id,
            'fecha_registro' => now(),
            'fecha_prestamo' => now()->toDateString(),
            'fecha_vencimiento' => now()->addDays(15)->toDateString(),
            'estado' => PrestamoDomiciliario::ESTADO_ACTIVO,
            'registrado_por' => $admin->id,
        ]);

        $respuesta = $this->actingAs($admin)->post(route('prestamos.store'), [
            'socio_id' => $socio2->id,
            'ejemplar_id' => $ejemplar->id,
            'fecha_prestamo' => now()->toDateString(),
        ]);

        $respuesta->assertSessionHasErrors('ejemplar_id');
        $this->assertSame(1, PrestamoDomiciliario::where('ejemplar_id', $ejemplar->id)->count());
    }

    public function test_un_socio_con_restriccion_activa_no_puede_recibir_un_prestamo(): void
    {
        $admin = User::factory()->create(['rol' => User::ROL_ADMINISTRADOR]);
        $socio = $this->crearSocio();
        $ejemplar = $this->crearEjemplarDisponible();

        RestriccionSocio::create([
            'socio_id' => $socio->id,
            'tipo' => 'automatica',
            'fecha_inicio' => now()->toDateString(),
            'fecha_fin' => now()->addDays(5)->toDateString(),
            'dias_atraso_origen' => 5,
        ]);

        $respuesta = $this->actingAs($admin)->post(route('prestamos.store'), [
            'socio_id' => $socio->id,
            'ejemplar_id' => $ejemplar->id,
            'fecha_prestamo' => now()->toDateString(),
        ]);

        $respuesta->assertSessionHasErrors('socio_id');
        $this->assertStringContainsString(
            now()->addDays(5)->format('d/m/Y'),
            session('errors')->first('socio_id')
        );
        $this->assertSame(0, PrestamoDomiciliario::count());
    }

    public function test_un_socio_con_restriccion_activa_y_excepcion_de_exencion_vigente_si_puede_recibir_el_prestamo(): void
    {
        $admin = User::factory()->create(['rol' => User::ROL_ADMINISTRADOR]);
        $socio = $this->crearSocio();
        $ejemplar = $this->crearEjemplarDisponible();

        RestriccionSocio::create([
            'socio_id' => $socio->id,
            'tipo' => 'automatica',
            'fecha_inicio' => now()->toDateString(),
            'fecha_fin' => now()->addDays(5)->toDateString(),
            'dias_atraso_origen' => 5,
        ]);

        ExcepcionAutorizada::create([
            'tipo' => ExcepcionAutorizada::TIPO_EXENCION_RESTRICCION,
            'entidad_afectada_type' => Socio::class,
            'entidad_afectada_id' => $socio->id,
            'autorizado_por' => $admin->id,
            'fecha_autorizacion' => now()->toDateString(),
            'motivo' => 'Situación excepcional autorizada por la Comisión Directiva.',
            'fecha_inicio' => now()->toDateString(),
            'fecha_fin' => now()->addDays(10)->toDateString(),
            'estado' => 'vigente',
        ]);

        $respuesta = $this->actingAs($admin)->post(route('prestamos.store'), [
            'socio_id' => $socio->id,
            'ejemplar_id' => $ejemplar->id,
            'fecha_prestamo' => now()->toDateString(),
        ]);

        $respuesta->assertRedirect();
        $prestamo = PrestamoDomiciliario::firstOrFail();
        $this->assertSame($socio->id, $prestamo->socio_id);
        // Criterio: "El registro del préstamo indica que se usó una excepción."
        $this->assertStringContainsString('excepción', mb_strtolower($prestamo->motivo_excepcion_limite ?? ''));
    }

    public function test_un_socio_estandar_con_3_prestamos_activos_recibe_alerta_al_intentar_un_cuarto(): void
    {
        $admin = User::factory()->create(['rol' => User::ROL_ADMINISTRADOR]);
        $tipoEstandar = TipoSocio::create([
            'nombre' => 'Estándar',
            'limite_prestamos_simultaneos' => 3,
            'sujeto_a_restriccion_automatica' => true,
        ]);
        $socio = $this->crearSocio($tipoEstandar);
        for ($i = 0; $i < 3; $i++) {
            $this->crearPrestamoActivo($socio, $admin);
        }
        $ejemplarNuevo = $this->crearEjemplarDisponible();

        $sinMotivo = $this->actingAs($admin)->post(route('prestamos.store'), [
            'socio_id' => $socio->id,
            'ejemplar_id' => $ejemplarNuevo->id,
            'fecha_prestamo' => now()->toDateString(),
        ]);
        $sinMotivo->assertSessionHasErrors('motivo_excepcion_limite');
        $this->assertSame(3, PrestamoDomiciliario::where('socio_id', $socio->id)->count());

        $conMotivo = $this->actingAs($admin)->post(route('prestamos.store'), [
            'socio_id' => $socio->id,
            'ejemplar_id' => $ejemplarNuevo->id,
            'fecha_prestamo' => now()->toDateString(),
            'motivo_excepcion_limite' => 'Autorizado por el bibliotecario a cargo.',
        ]);
        $conMotivo->assertRedirect();
        $prestamo = PrestamoDomiciliario::where('ejemplar_id', $ejemplarNuevo->id)->firstOrFail();
        $this->assertTrue($prestamo->es_excepcion_de_limite);
        $this->assertSame('Autorizado por el bibliotecario a cargo.', $prestamo->motivo_excepcion_limite);
    }

    /**
     * Criterio: "Un socio Honorario con 5 préstamos activos recibe la misma alerta de límite (su
     * límite es 5). El sistema usa el límite del Tipo de Socio, no un valor hardcodeado."
     */
    public function test_un_socio_honorario_con_5_prestamos_activos_recibe_alerta_usando_el_limite_de_su_tipo(): void
    {
        $admin = User::factory()->create(['rol' => User::ROL_ADMINISTRADOR]);
        $honorario = TipoSocio::create([
            'nombre' => 'Honorario',
            'limite_prestamos_simultaneos' => 5,
            'sujeto_a_restriccion_automatica' => false,
        ]);
        $socio = $this->crearSocio($honorario);
        for ($i = 0; $i < 5; $i++) {
            $this->crearPrestamoActivo($socio, $admin);
        }
        $ejemplarNuevo = $this->crearEjemplarDisponible();

        $respuesta = $this->actingAs($admin)->post(route('prestamos.store'), [
            'socio_id' => $socio->id,
            'ejemplar_id' => $ejemplarNuevo->id,
            'fecha_prestamo' => now()->toDateString(),
        ]);

        $respuesta->assertSessionHasErrors('motivo_excepcion_limite');
        $this->assertStringContainsString('límite de su tipo: 5', session('errors')->first('motivo_excepcion_limite'));
    }

    public function test_la_fecha_de_vencimiento_se_calcula_con_el_plazo_configurado_de_15_dias(): void
    {
        $admin = User::factory()->create(['rol' => User::ROL_ADMINISTRADOR]);
        $socio = $this->crearSocio();
        $ejemplar = $this->crearEjemplarDisponible();
        $fechaPrestamo = now()->subDays(2)->toDateString();

        $this->actingAs($admin)->post(route('prestamos.store'), [
            'socio_id' => $socio->id,
            'ejemplar_id' => $ejemplar->id,
            'fecha_prestamo' => $fechaPrestamo,
        ])->assertRedirect();

        $prestamo = PrestamoDomiciliario::firstOrFail();
        // RN-13: fecha_prestamo editable, distinta de fecha_registro (siempre "ahora").
        $this->assertSame($fechaPrestamo, $prestamo->fecha_prestamo->toDateString());
        $this->assertSame(now()->subDays(2)->addDays(15)->toDateString(), $prestamo->fecha_vencimiento->toDateString());
    }

    public function test_un_ejemplar_solo_sala_no_puede_prestarse(): void
    {
        $admin = User::factory()->create(['rol' => User::ROL_ADMINISTRADOR]);
        $socio = $this->crearSocio();
        $libro = Libro::create(['titulo' => 'Libro de sala']);
        $ejemplar = $libro->ejemplares()->create([
            'modalidad_acceso' => Ejemplar::MODALIDAD_SOLO_SALA,
            'fecha_ingreso' => '2020-01-01',
            'origen' => Ejemplar::ORIGEN_COMPRA,
        ]);

        $respuesta = $this->actingAs($admin)->post(route('prestamos.store'), [
            'socio_id' => $socio->id,
            'ejemplar_id' => $ejemplar->id,
            'fecha_prestamo' => now()->toDateString(),
        ]);

        $respuesta->assertSessionHasErrors('ejemplar_id');
        $this->assertSame(0, PrestamoDomiciliario::count());
    }

    public function test_un_ejemplar_restringido_sin_excepcion_vigente_no_puede_prestarse(): void
    {
        $admin = User::factory()->create(['rol' => User::ROL_ADMINISTRADOR]);
        $socio = $this->crearSocio();
        $libro = Libro::create(['titulo' => 'Libro restringido']);
        $ejemplar = $libro->ejemplares()->create([
            'modalidad_acceso' => Ejemplar::MODALIDAD_RESTRINGIDO,
            'fecha_ingreso' => '2020-01-01',
            'origen' => Ejemplar::ORIGEN_COMPRA,
        ]);

        $respuesta = $this->actingAs($admin)->post(route('prestamos.store'), [
            'socio_id' => $socio->id,
            'ejemplar_id' => $ejemplar->id,
            'fecha_prestamo' => now()->toDateString(),
        ]);

        $respuesta->assertSessionHasErrors('ejemplar_id');
        $this->assertSame(0, PrestamoDomiciliario::count());
    }

    public function test_un_ejemplar_restringido_con_excepcion_vigente_para_ese_ejemplar_si_puede_prestarse(): void
    {
        $admin = User::factory()->create(['rol' => User::ROL_ADMINISTRADOR]);
        $socio = $this->crearSocio();
        $libro = Libro::create(['titulo' => 'Libro restringido con autorización']);
        $ejemplar = $libro->ejemplares()->create([
            'modalidad_acceso' => Ejemplar::MODALIDAD_RESTRINGIDO,
            'fecha_ingreso' => '2020-01-01',
            'origen' => Ejemplar::ORIGEN_COMPRA,
        ]);

        ExcepcionAutorizada::create([
            'tipo' => ExcepcionAutorizada::TIPO_AUTORIZACION_MATERIAL_RESTRINGIDO,
            'entidad_afectada_type' => Ejemplar::class,
            'entidad_afectada_id' => $ejemplar->id,
            'autorizado_por' => $admin->id,
            'fecha_autorizacion' => now()->toDateString(),
            'motivo' => 'Investigador autorizado.',
            'fecha_inicio' => now()->toDateString(),
            'fecha_fin' => now()->addDays(10)->toDateString(),
            'estado' => 'vigente',
        ]);

        $respuesta = $this->actingAs($admin)->post(route('prestamos.store'), [
            'socio_id' => $socio->id,
            'ejemplar_id' => $ejemplar->id,
            'fecha_prestamo' => now()->toDateString(),
        ]);

        $respuesta->assertRedirect();
        $this->assertSame(1, PrestamoDomiciliario::where('ejemplar_id', $ejemplar->id)->count());
    }

    private function crearSocio(?TipoSocio $tipoSocio = null): Socio
    {
        $tipoSocio ??= TipoSocio::create([
            'nombre' => 'Estándar de prueba '.uniqid(),
            'limite_prestamos_simultaneos' => 3,
            'sujeto_a_restriccion_automatica' => true,
        ]);

        return Socio::create([
            'nombre_principal' => 'Socio de prueba '.uniqid(),
            'fecha_alta' => '2020-01-01',
            'tipo_socio_id' => $tipoSocio->id,
        ]);
    }

    private function crearEjemplarDisponible(): Ejemplar
    {
        $libro = Libro::create(['titulo' => 'Libro de prueba '.uniqid()]);

        return $libro->ejemplares()->create([
            'modalidad_acceso' => Ejemplar::MODALIDAD_LIBRE_CIRCULACION,
            'fecha_ingreso' => '2020-01-01',
            'origen' => Ejemplar::ORIGEN_COMPRA,
        ]);
    }

    private function crearPrestamoActivo(Socio $socio, User $registrador): PrestamoDomiciliario
    {
        return PrestamoDomiciliario::create([
            'ejemplar_id' => $this->crearEjemplarDisponible()->id,
            'socio_id' => $socio->id,
            'fecha_registro' => now(),
            'fecha_prestamo' => now()->toDateString(),
            'fecha_vencimiento' => now()->addDays(15)->toDateString(),
            'estado' => PrestamoDomiciliario::ESTADO_ACTIVO,
            'registrado_por' => $registrador->id,
        ]);
    }
}
