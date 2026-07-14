<?php

// Origen: Plan de Implementación v2, Módulo 2 — Catálogo, criterios de aceptación 3, 4 y 6, más
// RN-08/RN-09 (Ejemplar::puedeSalirDeLaBiblioteca(), Paso 7). Ejercita Ejemplar::estadoActual() y
// puedeSalirDeLaBiblioteca() reutilizándolos tal como los expone el modelo (Módulo 1), sin
// reimplementar su lógica en el test.

namespace Tests\Feature\Catalogo;

use App\Models\CustodiaExterna;
use App\Models\Ejemplar;
use App\Models\ExcepcionAutorizada;
use App\Models\Libro;
use App\Models\MovimientoInterno;
use App\Models\PrestamoDomiciliario;
use App\Models\Socio;
use App\Models\TipoSocio;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class EjemplarEstadoTest extends TestCase
{
    use RefreshDatabase;

    public function test_personal_puede_crear_un_ejemplar_con_modalidad_solo_sala_vinculado_a_un_libro_existente(): void
    {
        $personal = User::factory()->create(['rol' => User::ROL_PERSONAL]);
        $libro = Libro::create(['titulo' => 'Libro de prueba']);

        $respuesta = $this->actingAs($personal)->post(route('catalogo.libros.ejemplares.store', $libro), [
            'modalidad_acceso' => Ejemplar::MODALIDAD_SOLO_SALA,
            'fecha_ingreso' => '2026-01-01',
            'origen' => Ejemplar::ORIGEN_COMPRA,
        ]);

        $respuesta->assertRedirect(route('catalogo.libros.show', $libro));
        $this->assertDatabaseHas('ejemplares', [
            'libro_id' => $libro->id,
            'modalidad_acceso' => Ejemplar::MODALIDAD_SOLO_SALA,
        ]);
    }

    public function test_un_ejemplar_con_estado_manual_en_reparacion_muestra_ese_estado_sin_tener_movimiento_activo(): void
    {
        $admin = User::factory()->create(['rol' => User::ROL_ADMINISTRADOR]);
        $libro = Libro::create(['titulo' => 'Libro en reparación']);
        $ejemplar = Ejemplar::create([
            'libro_id' => $libro->id,
            'estado_manual' => Ejemplar::ESTADO_MANUAL_EN_REPARACION,
            'modalidad_acceso' => Ejemplar::MODALIDAD_LIBRE_CIRCULACION,
            'fecha_ingreso' => '2020-01-01',
            'origen' => Ejemplar::ORIGEN_COMPRA,
        ]);

        $this->assertFalse($ejemplar->tieneMovimientoActivo());
        $this->assertSame(Ejemplar::ESTADO_MANUAL_EN_REPARACION, $ejemplar->estadoActual());

        $respuesta = $this->actingAs($admin)->get(route('catalogo.libros.show', $libro));

        $respuesta->assertOk();
        $respuesta->assertSee(Ejemplar::ETIQUETAS_ESTADO[Ejemplar::ESTADO_MANUAL_EN_REPARACION]);
    }

    public function test_un_ejemplar_con_prestamo_domiciliario_activo_muestra_el_estado_prestado(): void
    {
        $admin = User::factory()->create(['rol' => User::ROL_ADMINISTRADOR]);
        $libro = Libro::create(['titulo' => 'Libro prestado']);
        $ejemplar = Ejemplar::create([
            'libro_id' => $libro->id,
            'modalidad_acceso' => Ejemplar::MODALIDAD_LIBRE_CIRCULACION,
            'fecha_ingreso' => '2020-01-01',
            'origen' => Ejemplar::ORIGEN_COMPRA,
        ]);
        $socio = $this->crearSocio();
        PrestamoDomiciliario::create([
            'ejemplar_id' => $ejemplar->id,
            'socio_id' => $socio->id,
            'fecha_registro' => now(),
            'fecha_prestamo' => now()->toDateString(),
            'fecha_vencimiento' => now()->addDays(14)->toDateString(),
            'estado' => 'activo',
            'registrado_por' => $admin->id,
        ]);

        $this->assertSame(Ejemplar::ESTADO_PRESTADO, $ejemplar->fresh()->estadoActual());

        $respuesta = $this->actingAs($admin)->get(route('catalogo.libros.show', $libro));

        $respuesta->assertOk();
        $respuesta->assertSee(Ejemplar::ETIQUETAS_ESTADO[Ejemplar::ESTADO_PRESTADO]);
    }

    // Origen: corrección 2026-07-14 (ver ADR-012). Ningún test del Paso 8 original renderizaba
    // catalogo.libros.show para un ejemplar "disponible" liso (sin estado_manual y sin préstamo
    // domiciliario activo) — que es justamente el caso por defecto y más común. estadoActual()
    // solo llega a evaluar movimientosInternos()/custodiasExternas() cuando las dos condiciones
    // previas (estado_manual, préstamo domiciliario) son falsas, así que este es el único camino
    // que efectivamente hubiera revelado en un test el error SQL que sí apareció en producción.
    public function test_un_ejemplar_disponible_sin_movimientos_muestra_estado_disponible_en_la_vista_de_detalle(): void
    {
        $admin = User::factory()->create(['rol' => User::ROL_ADMINISTRADOR]);
        $libro = Libro::create(['titulo' => 'Libro con ejemplar disponible']);
        $ejemplar = Ejemplar::create([
            'libro_id' => $libro->id,
            'modalidad_acceso' => Ejemplar::MODALIDAD_LIBRE_CIRCULACION,
            'fecha_ingreso' => '2020-01-01',
            'origen' => Ejemplar::ORIGEN_COMPRA,
        ]);

        $this->assertSame(Ejemplar::ESTADO_DISPONIBLE, $ejemplar->estadoActual());
        $this->assertFalse($ejemplar->tieneMovimientoActivo());

        $respuesta = $this->actingAs($admin)->get(route('catalogo.libros.show', $libro));

        $respuesta->assertOk();
        $respuesta->assertSee(Ejemplar::ETIQUETAS_ESTADO[Ejemplar::ESTADO_DISPONIBLE]);
    }

    // Origen: corrección 2026-07-14 (ver ADR-012). Cierra la brecha de cobertura de RN-04 (Nivel 2,
    // Ejemplar::tieneMovimientoActivo()) para movimiento interno: el Paso 8 original nunca creaba un
    // registro real de MovimientoInterno vinculado a un Ejemplar, así que el nombre de columna
    // incorrecto en la relación (ver Ejemplar::movimientosInternos()) nunca se ejercitó contra datos
    // reales hasta la primera ejecución en el entorno del usuario.
    public function test_un_ejemplar_con_movimiento_interno_activo_tiene_movimiento_activo_y_muestra_ese_estado(): void
    {
        $admin = User::factory()->create(['rol' => User::ROL_ADMINISTRADOR]);
        $libro = Libro::create(['titulo' => 'Libro con ejemplar en movimiento interno']);
        $ejemplar = Ejemplar::create([
            'libro_id' => $libro->id,
            'modalidad_acceso' => Ejemplar::MODALIDAD_LIBRE_CIRCULACION,
            'fecha_ingreso' => '2020-01-01',
            'origen' => Ejemplar::ORIGEN_COMPRA,
        ]);
        $movimiento = MovimientoInterno::create([
            'responsable_id' => $admin->id,
            'proposito' => 'Exhibición temporal',
            'fecha_inicio' => now()->toDateString(),
            'fecha_retorno_esperada' => now()->addDays(7)->toDateString(),
            'estado' => 'activo',
        ]);
        $ejemplar->movimientosInternos()->attach($movimiento->id, ['fecha_retorno_efectiva' => null]);

        $this->assertTrue($ejemplar->tieneMovimientoActivo());
        $this->assertSame(Ejemplar::ESTADO_EN_MOVIMIENTO_INTERNO, $ejemplar->estadoActual());

        $respuesta = $this->actingAs($admin)->get(route('catalogo.libros.show', $libro));

        $respuesta->assertOk();
        $respuesta->assertSee(Ejemplar::ETIQUETAS_ESTADO[Ejemplar::ESTADO_EN_MOVIMIENTO_INTERNO]);
    }

    // Origen: corrección 2026-07-14 (ver ADR-012). Mismo caso que el anterior, para custodia
    // externa (el otro tipo de movimiento afectado por el mismo defecto de nombre de columna).
    public function test_un_ejemplar_en_custodia_externa_activa_tiene_movimiento_activo_y_muestra_ese_estado(): void
    {
        $admin = User::factory()->create(['rol' => User::ROL_ADMINISTRADOR]);
        $libro = Libro::create(['titulo' => 'Libro con ejemplar en custodia externa']);
        $ejemplar = Ejemplar::create([
            'libro_id' => $libro->id,
            'modalidad_acceso' => Ejemplar::MODALIDAD_LIBRE_CIRCULACION,
            'fecha_ingreso' => '2020-01-01',
            'origen' => Ejemplar::ORIGEN_COMPRA,
        ]);
        $custodia = CustodiaExterna::create([
            'institucion_o_evento_custodio' => 'Museo Nacional',
            'fecha_salida' => now()->toDateString(),
            'fecha_retorno_esperada' => now()->addDays(30)->toDateString(),
            'estado' => 'activa',
        ]);
        $ejemplar->custodiasExternas()->attach($custodia->id, ['fecha_retorno_efectiva' => null]);

        $this->assertTrue($ejemplar->tieneMovimientoActivo());
        $this->assertSame(Ejemplar::ESTADO_EN_CUSTODIA_EXTERNA, $ejemplar->estadoActual());

        $respuesta = $this->actingAs($admin)->get(route('catalogo.libros.show', $libro));

        $respuesta->assertOk();
        $respuesta->assertSee(Ejemplar::ETIQUETAS_ESTADO[Ejemplar::ESTADO_EN_CUSTODIA_EXTERNA]);
    }

    public function test_un_ejemplar_solo_sala_nunca_puede_salir_de_la_biblioteca(): void
    {
        // RN-08.
        $ejemplar = Ejemplar::create([
            'libro_id' => Libro::create(['titulo' => 'Libro solo sala'])->id,
            'modalidad_acceso' => Ejemplar::MODALIDAD_SOLO_SALA,
            'fecha_ingreso' => '2020-01-01',
            'origen' => Ejemplar::ORIGEN_COMPRA,
        ]);

        $this->assertFalse($ejemplar->puedeSalirDeLaBiblioteca());
    }

    public function test_un_ejemplar_restringido_no_puede_salir_sin_excepcion_autorizada_vigente(): void
    {
        // RN-09.
        $ejemplar = Ejemplar::create([
            'libro_id' => Libro::create(['titulo' => 'Libro restringido'])->id,
            'modalidad_acceso' => Ejemplar::MODALIDAD_RESTRINGIDO,
            'fecha_ingreso' => '2020-01-01',
            'origen' => Ejemplar::ORIGEN_COMPRA,
        ]);

        $this->assertFalse($ejemplar->puedeSalirDeLaBiblioteca());
    }

    public function test_un_ejemplar_restringido_si_puede_salir_con_una_excepcion_autorizada_vigente_para_ese_ejemplar(): void
    {
        // RN-09/RN-10/RN-11.
        $autorizador = User::factory()->create(['rol' => User::ROL_ADMINISTRADOR]);
        $ejemplar = Ejemplar::create([
            'libro_id' => Libro::create(['titulo' => 'Libro restringido con excepción'])->id,
            'modalidad_acceso' => Ejemplar::MODALIDAD_RESTRINGIDO,
            'fecha_ingreso' => '2020-01-01',
            'origen' => Ejemplar::ORIGEN_COMPRA,
        ]);
        ExcepcionAutorizada::create([
            'tipo' => ExcepcionAutorizada::TIPO_AUTORIZACION_MATERIAL_RESTRINGIDO,
            'entidad_afectada_type' => Ejemplar::class,
            'entidad_afectada_id' => $ejemplar->id,
            'autorizado_por' => $autorizador->id,
            'fecha_autorizacion' => now()->toDateString(),
            'motivo' => 'Préstamo excepcional autorizado para prueba.',
            'fecha_inicio' => now()->toDateString(),
            'fecha_fin' => null, // Indefinida hasta revocación explícita (RN-11).
            'estado' => 'vigente',
        ]);

        $this->assertTrue($ejemplar->puedeSalirDeLaBiblioteca());
    }

    public function test_un_ejemplar_restringido_no_puede_salir_si_la_excepcion_vigente_es_de_otro_ejemplar(): void
    {
        // Prueba que la verificación esté correctamente acotada por entidad_afectada_id (RN-09) y
        // no autorice de forma cruzada a cualquier ejemplar restringido.
        $autorizador = User::factory()->create(['rol' => User::ROL_ADMINISTRADOR]);
        $libro = Libro::create(['titulo' => 'Libro con dos ejemplares restringidos']);
        $ejemplarAutorizado = Ejemplar::create([
            'libro_id' => $libro->id,
            'modalidad_acceso' => Ejemplar::MODALIDAD_RESTRINGIDO,
            'fecha_ingreso' => '2020-01-01',
            'origen' => Ejemplar::ORIGEN_COMPRA,
        ]);
        $ejemplarSinAutorizar = Ejemplar::create([
            'libro_id' => $libro->id,
            'modalidad_acceso' => Ejemplar::MODALIDAD_RESTRINGIDO,
            'fecha_ingreso' => '2021-01-01',
            'origen' => Ejemplar::ORIGEN_COMPRA,
        ]);
        ExcepcionAutorizada::create([
            'tipo' => ExcepcionAutorizada::TIPO_AUTORIZACION_MATERIAL_RESTRINGIDO,
            'entidad_afectada_type' => Ejemplar::class,
            'entidad_afectada_id' => $ejemplarAutorizado->id,
            'autorizado_por' => $autorizador->id,
            'fecha_autorizacion' => now()->toDateString(),
            'motivo' => 'Excepción para el otro ejemplar.',
            'fecha_inicio' => now()->toDateString(),
            'estado' => 'vigente',
        ]);

        $this->assertFalse($ejemplarSinAutorizar->puedeSalirDeLaBiblioteca());
        $this->assertTrue($ejemplarAutorizado->puedeSalirDeLaBiblioteca());
    }

    public function test_un_ejemplar_restringido_no_puede_salir_si_la_excepcion_ya_esta_revocada(): void
    {
        $autorizador = User::factory()->create(['rol' => User::ROL_ADMINISTRADOR]);
        $ejemplar = Ejemplar::create([
            'libro_id' => Libro::create(['titulo' => 'Libro con excepción revocada'])->id,
            'modalidad_acceso' => Ejemplar::MODALIDAD_RESTRINGIDO,
            'fecha_ingreso' => '2020-01-01',
            'origen' => Ejemplar::ORIGEN_COMPRA,
        ]);
        ExcepcionAutorizada::create([
            'tipo' => ExcepcionAutorizada::TIPO_AUTORIZACION_MATERIAL_RESTRINGIDO,
            'entidad_afectada_type' => Ejemplar::class,
            'entidad_afectada_id' => $ejemplar->id,
            'autorizado_por' => $autorizador->id,
            'fecha_autorizacion' => now()->subDays(10)->toDateString(),
            'motivo' => 'Excepción ya revocada.',
            'fecha_inicio' => now()->subDays(10)->toDateString(),
            'estado' => 'revocada',
            'revocado_por' => $autorizador->id,
            'fecha_revocacion' => now()->toDateString(),
        ]);

        $this->assertFalse($ejemplar->puedeSalirDeLaBiblioteca());
    }

    private function crearSocio(): Socio
    {
        $tipoSocio = TipoSocio::create([
            'nombre' => 'Estándar de prueba',
            'limite_prestamos_simultaneos' => 3,
            'sujeto_a_restriccion_automatica' => true,
        ]);

        return Socio::create([
            'nombre_principal' => 'Socio de prueba',
            'fecha_alta' => '2020-01-01',
            'tipo_socio_id' => $tipoSocio->id,
        ]);
    }
}
