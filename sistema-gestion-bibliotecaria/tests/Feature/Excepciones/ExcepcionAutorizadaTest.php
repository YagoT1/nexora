<?php

// Origen: Plan de Implementación v2, Módulo 6 — Excepciones y restricciones. Ver
// Fase 6 - Development/BRIEFING-MODULO-6-EXCEPCIONES-RESTRICCIONES.md. Cubre los criterios de
// aceptación 2, 3, 5 y 6 (literales del plan). El criterio 4 (socio con excepción de exención
// vigente puede recibir préstamo con restricción activa) ya está cubierto desde el Módulo 4 en
// RegistroPrestamoTest::test_un_socio_con_restriccion_activa_y_excepcion_de_exencion_vigente_si_puede_recibir_el_prestamo()
// — no se duplica acá (Decisión D-18 no cambió ningún comportamiento observable de ese test).

namespace Tests\Feature\Excepciones;

use App\Models\Ejemplar;
use App\Models\ExcepcionAutorizada;
use App\Models\Libro;
use App\Models\PrestamoDomiciliario;
use App\Models\RestriccionSocio;
use App\Models\Socio;
use App\Models\TipoSocio;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class ExcepcionAutorizadaTest extends TestCase
{
    use RefreshDatabase;

    /**
     * Criterios 2 y 3 (literales): "Un Administrador puede crear una excepción de exención ... con
     * motivo ... y vigencia indefinida" + "La excepción queda registrada con el nombre del
     * Administrador que la creó y la fecha."
     */
    public function test_un_administrador_puede_crear_una_excepcion_con_vigencia_indefinida(): void
    {
        $admin = User::factory()->create(['rol' => User::ROL_ADMINISTRADOR, 'name' => 'Directora de la Biblioteca']);
        $socio = $this->crearSocio();

        $respuesta = $this->actingAs($admin)->post(route('excepciones.store'), [
            'tipo' => ExcepcionAutorizada::TIPO_EXENCION_RESTRICCION,
            'entidad_id' => $socio->id,
            'motivo' => 'Colaboración histórica con la institución',
            'fecha_inicio' => now()->toDateString(),
            'fecha_fin' => '',
        ]);

        $respuesta->assertRedirect(route('excepciones.index'));

        $excepcion = ExcepcionAutorizada::firstOrFail();
        $this->assertSame(Socio::class, $excepcion->entidad_afectada_type);
        $this->assertSame($socio->id, $excepcion->entidad_afectada_id);
        $this->assertSame('Colaboración histórica con la institución', $excepcion->motivo);
        // Vigencia indefinida: fecha_fin null (RN-11), no una fecha lejana arbitraria.
        $this->assertNull($excepcion->fecha_fin);
        $this->assertSame(ExcepcionAutorizada::ESTADO_VIGENTE, $excepcion->estado);
        // Criterio 3: registrada con el Administrador que la creó y la fecha — nunca tomados del
        // formulario (RN-11), siempre fijados por el servidor.
        $this->assertSame($admin->id, $excepcion->autorizado_por);
        $this->assertSame($admin->id, $excepcion->autorizadoPor->id);
        $this->assertSame('Directora de la Biblioteca', $excepcion->autorizadoPor->name);
        $this->assertSame(now()->toDateString(), $excepcion->fecha_autorizacion->toDateString());
    }

    /** RN-11: autorizado_por/fecha_autorizacion no son tomados del formulario aunque se envíen. */
    public function test_el_formulario_no_puede_sobrescribir_quien_autoriza_ni_la_fecha(): void
    {
        $admin = User::factory()->create(['rol' => User::ROL_ADMINISTRADOR]);
        $otroAdmin = User::factory()->create(['rol' => User::ROL_ADMINISTRADOR]);
        $socio = $this->crearSocio();

        $this->actingAs($admin)->post(route('excepciones.store'), [
            'tipo' => ExcepcionAutorizada::TIPO_EXENCION_RESTRICCION,
            'entidad_id' => $socio->id,
            'motivo' => 'Prueba de integridad.',
            'fecha_inicio' => now()->toDateString(),
            // Un atacante o un formulario manipulado podría intentar enviar estos campos.
            'autorizado_por' => $otroAdmin->id,
            'fecha_autorizacion' => now()->subYears(5)->toDateString(),
        ]);

        $excepcion = ExcepcionAutorizada::firstOrFail();
        $this->assertSame($admin->id, $excepcion->autorizado_por);
        $this->assertSame(now()->toDateString(), $excepcion->fecha_autorizacion->toDateString());
    }

    /** Criterio 6 (literal): revocación antes de la fecha de fin, registrada con fecha y usuario. */
    public function test_un_administrador_puede_revocar_una_excepcion_vigente(): void
    {
        $admin = User::factory()->create(['rol' => User::ROL_ADMINISTRADOR]);
        $excepcion = $this->crearExcepcionVigente($admin, now()->addDays(30));

        $respuesta = $this->actingAs($admin)->patch(route('excepciones.revocar', $excepcion));

        $respuesta->assertRedirect();
        $excepcion->refresh();
        $this->assertSame(ExcepcionAutorizada::ESTADO_REVOCADA, $excepcion->estado);
        $this->assertSame($admin->id, $excepcion->revocado_por);
        $this->assertSame(now()->toDateString(), $excepcion->fecha_revocacion->toDateString());
    }

    public function test_no_se_puede_revocar_dos_veces_la_misma_excepcion(): void
    {
        $admin = User::factory()->create(['rol' => User::ROL_ADMINISTRADOR]);
        $excepcion = $this->crearExcepcionVigente($admin, now()->addDays(30));
        $excepcion->update([
            'estado' => ExcepcionAutorizada::ESTADO_REVOCADA,
            'revocado_por' => $admin->id,
            'fecha_revocacion' => now()->subDay(),
        ]);

        $respuesta = $this->actingAs($admin)->patch(route('excepciones.revocar', $excepcion));

        $respuesta->assertNotFound();
    }

    /**
     * Criterio 5 (literal, primera mitad): "Una excepción con fecha de fin pasada aparece con
     * estado 'Vencida'" — estadoVisible() (Decisión D-15), sin que la columna `estado` almacenada
     * cambie (sigue en 'vigente', ver Decisión D-15).
     */
    public function test_una_excepcion_con_fecha_de_fin_pasada_aparece_como_vencida_sin_reescribir_la_columna(): void
    {
        $admin = User::factory()->create(['rol' => User::ROL_ADMINISTRADOR]);
        $excepcion = $this->crearExcepcionVigente($admin, now()->subDay());

        $this->assertSame(ExcepcionAutorizada::ESTADO_VENCIDA, $excepcion->estadoVisible());
        // La columna real nunca se reescribe a 'vencida' (D-15) — solo el cómputo de lectura cambia.
        $this->assertSame(ExcepcionAutorizada::ESTADO_VIGENTE, $excepcion->getRawOriginal('estado'));
    }

    /**
     * Criterio 5 (literal, segunda mitad): "... y no aplica en las validaciones de préstamo." Un
     * socio con restricción activa y una excepción de exención cuya fecha de fin ya pasó debe
     * seguir bloqueado, exactamente como si no tuviera ninguna excepción.
     */
    public function test_una_excepcion_vencida_no_habilita_el_prestamo_de_un_socio_con_restriccion_activa(): void
    {
        $admin = User::factory()->create(['rol' => User::ROL_ADMINISTRADOR]);
        $socio = $this->crearSocio();
        $ejemplar = $this->crearEjemplarDisponible();

        RestriccionSocio::create([
            'socio_id' => $socio->id,
            'tipo' => RestriccionSocio::TIPO_AUTOMATICA,
            'fecha_inicio' => now()->subDays(10)->toDateString(),
            'fecha_fin' => now()->addDays(5)->toDateString(),
            'dias_atraso_origen' => 5,
        ]);

        $this->crearExcepcionVigente($admin, now()->subDay(), $socio, ExcepcionAutorizada::TIPO_EXENCION_RESTRICCION);

        $respuesta = $this->actingAs($admin)->post(route('prestamos.store'), [
            'socio_id' => $socio->id,
            'ejemplar_id' => $ejemplar->id,
            'fecha_prestamo' => now()->toDateString(),
        ]);

        $respuesta->assertSessionHasErrors('socio_id');
        $this->assertSame(0, PrestamoDomiciliario::count());
    }

    private function crearSocio(): Socio
    {
        $tipoSocio = TipoSocio::create([
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

    private function crearExcepcionVigente(
        User $admin,
        $fechaFin,
        ?Socio $socio = null,
        string $tipo = ExcepcionAutorizada::TIPO_EXENCION_RESTRICCION,
    ): ExcepcionAutorizada {
        return ExcepcionAutorizada::create([
            'tipo' => $tipo,
            'entidad_afectada_type' => Socio::class,
            'entidad_afectada_id' => ($socio ?? $this->crearSocio())->id,
            'autorizado_por' => $admin->id,
            'fecha_autorizacion' => now(),
            'motivo' => 'Excepción de prueba.',
            'fecha_inicio' => now()->subDays(2)->toDateString(),
            'fecha_fin' => $fechaFin->toDateString(),
            'estado' => ExcepcionAutorizada::ESTADO_VIGENTE,
        ]);
    }
}
