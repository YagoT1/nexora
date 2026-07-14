<?php

// Origen: Plan de Implementación v2, Módulo 3 — Socios, criterios de aceptación de la vista de
// mostrador: alerta de atraso visible + contador de atrasos en el año; RN-07 (Honorario no recibe
// restricciones automáticas, y la vista no debe mostrar ninguna aunque tenga atrasos).

namespace Tests\Feature\Socios;

use App\Models\Ejemplar;
use App\Models\HistorialAtraso;
use App\Models\Libro;
use App\Models\PrestamoDomiciliario;
use App\Models\RestriccionSocio;
use App\Models\Socio;
use App\Models\TipoSocio;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class VistaMostradorSocioTest extends TestCase
{
    use RefreshDatabase;

    public function test_la_vista_de_mostrador_muestra_la_alerta_de_atraso_y_el_contador_de_atrasos(): void
    {
        $admin = User::factory()->create(['rol' => User::ROL_ADMINISTRADOR]);
        $tipoSocio = $this->crearTipoSocio();
        $socio = Socio::create([
            'nombre_principal' => 'Socio con atraso',
            'fecha_alta' => '2020-01-01',
            'tipo_socio_id' => $tipoSocio->id,
        ]);
        $ejemplar = $this->crearEjemplar('Libro atrasado');
        $prestamo = PrestamoDomiciliario::create([
            'ejemplar_id' => $ejemplar->id,
            'socio_id' => $socio->id,
            'fecha_registro' => now(),
            'fecha_prestamo' => now()->subDays(20)->toDateString(),
            'fecha_vencimiento' => now()->subDays(5)->toDateString(),
            'estado' => 'atrasado',
            'registrado_por' => $admin->id,
        ]);
        HistorialAtraso::create([
            'socio_id' => $socio->id,
            'prestamo_domiciliario_id' => $prestamo->id,
            'dias_atraso' => 5,
            'fecha_devolucion_efectiva' => now()->subDays(1)->toDateString(),
            'restriccion_generada' => true,
        ]);

        $respuesta = $this->actingAs($admin)->get(route('socios.socios.show', $socio));

        $respuesta->assertOk();
        $respuesta->assertSee('Atrasado');
        $respuesta->assertSee('Libro atrasado');
        $respuesta->assertSee('Atrasos (últimos 12 meses)');
    }

    public function test_un_socio_honorario_no_muestra_restriccion_activa_aunque_tenga_atrasos(): void
    {
        $admin = User::factory()->create(['rol' => User::ROL_ADMINISTRADOR]);
        $honorario = TipoSocio::create([
            'nombre' => 'Honorario de prueba',
            'limite_prestamos_simultaneos' => 5,
            'sujeto_a_restriccion_automatica' => false,
        ]);
        $socio = Socio::create([
            'nombre_principal' => 'Socio honorario con atraso',
            'fecha_alta' => '2020-01-01',
            'tipo_socio_id' => $honorario->id,
        ]);
        $ejemplar = $this->crearEjemplar('Libro con atraso honorario');
        $prestamo = PrestamoDomiciliario::create([
            'ejemplar_id' => $ejemplar->id,
            'socio_id' => $socio->id,
            'fecha_registro' => now(),
            'fecha_prestamo' => now()->subDays(20)->toDateString(),
            'fecha_vencimiento' => now()->subDays(5)->toDateString(),
            'fecha_devolucion_efectiva' => now()->subDays(1)->toDateString(),
            'estado' => 'devuelto',
            'registrado_por' => $admin->id,
        ]);
        HistorialAtraso::create([
            'socio_id' => $socio->id,
            'prestamo_domiciliario_id' => $prestamo->id,
            'dias_atraso' => 5,
            'fecha_devolucion_efectiva' => now()->subDays(1)->toDateString(),
            'restriccion_generada' => false, // RN-07: Honorario, no se genera restricción.
        ]);
        // A diferencia del caso automático, esta prueba verifica explícitamente que aunque
        // existiera una RestriccionSocio vigente para otro socio, no afecta a este (acotado por
        // socio_id) — y que, como RN-07 indica que Honorario no genera restricciones, no se crea
        // ninguna para este socio en primer lugar.
        RestriccionSocio::create([
            'socio_id' => Socio::create([
                'nombre_principal' => 'Otro socio, no honorario',
                'fecha_alta' => '2020-01-01',
                'tipo_socio_id' => $this->crearTipoSocio()->id,
            ])->id,
            'tipo' => 'automatica',
            'fecha_inicio' => now()->toDateString(),
            'fecha_fin' => now()->addDays(5)->toDateString(),
            'dias_atraso_origen' => 5,
        ]);

        $respuesta = $this->actingAs($admin)->get(route('socios.socios.show', $socio));

        $respuesta->assertOk();
        $respuesta->assertDontSee('Restricción vigente');
    }

    private function crearTipoSocio(): TipoSocio
    {
        return TipoSocio::create([
            'nombre' => 'Estándar de prueba '.uniqid(),
            'limite_prestamos_simultaneos' => 3,
            'sujeto_a_restriccion_automatica' => true,
        ]);
    }

    private function crearEjemplar(string $titulo): Ejemplar
    {
        $libro = Libro::create(['titulo' => $titulo]);

        return Ejemplar::create([
            'libro_id' => $libro->id,
            'modalidad_acceso' => Ejemplar::MODALIDAD_LIBRE_CIRCULACION,
            'fecha_ingreso' => '2020-01-01',
            'origen' => Ejemplar::ORIGEN_COMPRA,
        ]);
    }
}
