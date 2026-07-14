<?php

// Origen: preparación de revisión funcional del Módulo 3 — Socios (Pasos 1 a 6: Tipo de Socio,
// Socio, búsqueda, vista de mostrador, historial). Mismo criterio que CatalogoDemoSeeder (Módulo
// 2): datos ficticios, idempotentes, solo para desarrollo/staging (nunca en producción).
//
// Casos cubiertos deliberadamente:
// - Un socio con nombre acentuado ("García") y un nombre alternativo, para poder probar en la UI
//   que buscar "Garcia" (sin tilde) lo encuentra (R-1 del briefing, extensión `unaccent`).
// - Un socio Estándar con un préstamo atrasado y su historial de atraso correspondiente, para ver
//   en la vista de mostrador la alerta de atraso y el contador de atrasos en los últimos 12 meses.
// - Un socio Honorario con el mismo tipo de atraso, para comprobar visualmente que RN-07 se
//   respeta: no muestra ninguna restricción activa aunque tenga atrasos (a diferencia del anterior,
//   a este no se le genera RestriccionSocio).

namespace Database\Seeders;

use App\Models\Ejemplar;
use App\Models\HistorialAtraso;
use App\Models\Libro;
use App\Models\PrestamoDomiciliario;
use App\Models\RestriccionSocio;
use App\Models\Socio;
use App\Models\TipoSocio;
use App\Models\User;
use Illuminate\Database\Seeder;

class SociosDemoSeeder extends Seeder
{
    public function run(): void
    {
        if (app()->environment('production')) {
            return; // Nunca crear datos de demostración en producción.
        }

        $estandar = TipoSocio::firstOrCreate(
            ['nombre' => 'Estándar'],
            ['limite_prestamos_simultaneos' => 3, 'sujeto_a_restriccion_automatica' => true]
        );
        $honorario = TipoSocio::firstOrCreate(
            ['nombre' => 'Honorario'],
            ['limite_prestamos_simultaneos' => 5, 'sujeto_a_restriccion_automatica' => false]
        );

        // Caso 1: búsqueda tolerante a acentos (R-1).
        Socio::firstOrCreate(
            ['dni' => '10000001'],
            [
                'nombre_principal' => 'María García',
                'nombres_alternativos' => ['Maria Garcia de los Santos'],
                'fecha_alta' => '2022-03-01',
                'estado' => 'activo',
                'tipo_socio_id' => $estandar->id,
            ]
        );

        // Registrador de los préstamos de demostración (requiere un usuario ya existente).
        $registrador = User::where('rol', User::ROL_ADMINISTRADOR)->first();
        if (! $registrador) {
            return; // AdminUserSeeder no corrió todavía en este entorno; nada más que sembrar.
        }

        // Caso 2: socio Estándar con préstamo atrasado — dispara RN-07 en su forma "normal"
        // (sujeto a restricción automática, aunque el Módulo 3 no la genera, solo la lee).
        $socioConAtraso = Socio::firstOrCreate(
            ['dni' => '10000002'],
            [
                'nombre_principal' => 'Roberto Fernández',
                'fecha_alta' => '2021-08-15',
                'estado' => 'activo',
                'tipo_socio_id' => $estandar->id,
            ]
        );
        $this->crearPrestamoAtrasadoConHistorial($socioConAtraso, $registrador, 'Manual de bibliotecología', true);

        // Caso 3: socio Honorario con el mismo tipo de atraso — RN-07: no debe generarse
        // RestriccionSocio, y la vista de mostrador no debe mostrar ninguna restricción activa.
        $socioHonorario = Socio::firstOrCreate(
            ['dni' => '10000003'],
            [
                'nombre_principal' => 'Elena Sánchez',
                'fecha_alta' => '2018-01-10',
                'estado' => 'activo',
                'tipo_socio_id' => $honorario->id,
            ]
        );
        $this->crearPrestamoAtrasadoConHistorial($socioHonorario, $registrador, 'Manual de encuadernación', false);
    }

    /**
     * Crea (si no existe) un Libro/Ejemplar dedicado, un PrestamoDomiciliario atrasado y su
     * HistorialAtraso correspondiente. $generaRestriccion controla si además se crea una
     * RestriccionSocio vigente (caso Estándar) o no (caso Honorario, RN-07).
     */
    private function crearPrestamoAtrasadoConHistorial(Socio $socio, User $registrador, string $tituloLibro, bool $generaRestriccion): void
    {
        $libro = Libro::firstOrCreate(['titulo' => $tituloLibro]);
        $ejemplar = $libro->ejemplares()->first();
        if (! $ejemplar) {
            $ejemplar = $libro->ejemplares()->create([
                'modalidad_acceso' => Ejemplar::MODALIDAD_LIBRE_CIRCULACION,
                'fecha_ingreso' => '2020-01-01',
                'origen' => Ejemplar::ORIGEN_COMPRA,
            ]);
        }

        if ($ejemplar->prestamosDomiciliarios()->exists()) {
            return; // Ya sembrado en una corrida anterior.
        }

        $prestamo = PrestamoDomiciliario::create([
            'ejemplar_id' => $ejemplar->id,
            'socio_id' => $socio->id,
            'fecha_registro' => now()->subDays(20),
            'fecha_prestamo' => now()->subDays(20)->toDateString(),
            'fecha_vencimiento' => now()->subDays(5)->toDateString(),
            'estado' => 'atrasado',
            'registrado_por' => $registrador->id,
        ]);

        HistorialAtraso::create([
            'socio_id' => $socio->id,
            'prestamo_domiciliario_id' => $prestamo->id,
            'dias_atraso' => 5,
            'fecha_devolucion_efectiva' => now()->subDays(1)->toDateString(),
            'restriccion_generada' => $generaRestriccion,
        ]);

        if ($generaRestriccion) {
            RestriccionSocio::create([
                'socio_id' => $socio->id,
                'tipo' => 'automatica',
                'fecha_inicio' => now()->subDays(1)->toDateString(),
                'fecha_fin' => now()->addDays(4)->toDateString(),
                'dias_atraso_origen' => 5,
                'prestamo_domiciliario_id' => $prestamo->id,
            ]);
        }
    }
}
