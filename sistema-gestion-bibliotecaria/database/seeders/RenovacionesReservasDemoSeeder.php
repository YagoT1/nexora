<?php

// Origen: preparación de revisión funcional del Módulo 5 — Renovaciones y reservas. Mismo criterio
// que los seeders de demostración anteriores: datos ficticios, idempotentes, solo para
// desarrollo/staging (nunca en producción).
//
// Casos cubiertos deliberadamente (uno por cada criterio de aceptación revisable manualmente):
// - Un préstamo activo sin ninguna reserva sobre su libro — permite probar la renovación exitosa
//   (RN-19: nueva fecha de vencimiento, registro de Renovación con la fecha anterior).
// - Un préstamo activo cuyo libro SÍ tiene una reserva pendiente de otro socio — permite probar el
//   rechazo de la renovación con el mensaje exacto (RN-03), sin tocar ningún dato del caso anterior.
// - Un libro sin ejemplares disponibles y sin reservas todavía — permite probar el alta de una
//   reserva nueva desde cero (CU-2 del briefing).
// - Una reserva ya en estado 'personal_alertado', con su fecha límite de retiro ya calculada —
//   permite ver de inmediato la columna "Retirar antes de" en el panel de devolución y en la vista
//   de mostrador del socio, sin tener que forzar una devolución primero.

namespace Database\Seeders;

use App\Models\Ejemplar;
use App\Models\Libro;
use App\Models\PrestamoDomiciliario;
use App\Models\Reserva;
use App\Models\Socio;
use App\Models\TipoSocio;
use App\Models\User;
use Illuminate\Database\Seeder;

class RenovacionesReservasDemoSeeder extends Seeder
{
    public function run(): void
    {
        if (app()->environment('production')) {
            return;
        }

        $registrador = User::where('rol', User::ROL_ADMINISTRADOR)->first();
        if (! $registrador) {
            return; // AdminUserSeeder no corrió todavía en este entorno.
        }

        $estandar = TipoSocio::firstOrCreate(
            ['nombre' => 'Estándar'],
            ['limite_prestamos_simultaneos' => 3, 'sujeto_a_restriccion_automatica' => true]
        );

        $this->sembrarPrestamoRenovableSinReservas($estandar, $registrador);
        $this->sembrarPrestamoBloqueadoPorReservaPendiente($estandar, $registrador);
        $this->sembrarLibroReservableSinReservas();
        $this->sembrarReservaYaPersonalAlertado($estandar);
    }

    private function sembrarPrestamoRenovableSinReservas(TipoSocio $estandar, User $registrador): void
    {
        $socio = Socio::firstOrCreate(
            ['dni' => '20000005'],
            [
                'nombre_principal' => 'Roberto Sosa',
                'fecha_alta' => '2021-03-01',
                'estado' => 'activo',
                'tipo_socio_id' => $estandar->id,
            ]
        );

        $libro = Libro::firstOrCreate(['titulo' => 'Manual de jardinería urbana']);
        $ejemplar = $libro->ejemplares()->first() ?? $libro->ejemplares()->create([
            'modalidad_acceso' => Ejemplar::MODALIDAD_LIBRE_CIRCULACION,
            'fecha_ingreso' => '2021-06-01',
            'origen' => Ejemplar::ORIGEN_COMPRA,
        ]);

        if (! $ejemplar->prestamosDomiciliarios()->whereIn('estado', PrestamoDomiciliario::ESTADOS_ABIERTOS)->exists()) {
            PrestamoDomiciliario::create([
                'ejemplar_id' => $ejemplar->id,
                'socio_id' => $socio->id,
                'fecha_registro' => now()->subDays(10),
                'fecha_prestamo' => now()->subDays(10)->toDateString(),
                'fecha_vencimiento' => now()->addDays(5)->toDateString(),
                'estado' => PrestamoDomiciliario::ESTADO_ACTIVO,
                'registrado_por' => $registrador->id,
            ]);
        }
    }

    private function sembrarPrestamoBloqueadoPorReservaPendiente(TipoSocio $estandar, User $registrador): void
    {
        $socio = Socio::firstOrCreate(
            ['dni' => '20000006'],
            [
                'nombre_principal' => 'Valeria Núñez',
                'fecha_alta' => '2020-11-15',
                'estado' => 'activo',
                'tipo_socio_id' => $estandar->id,
            ]
        );

        $libro = Libro::firstOrCreate(['titulo' => 'Introducción a la astronomía']);
        $ejemplar = $libro->ejemplares()->first() ?? $libro->ejemplares()->create([
            'modalidad_acceso' => Ejemplar::MODALIDAD_LIBRE_CIRCULACION,
            'fecha_ingreso' => '2019-08-01',
            'origen' => Ejemplar::ORIGEN_COMPRA,
        ]);

        if (! $ejemplar->prestamosDomiciliarios()->whereIn('estado', PrestamoDomiciliario::ESTADOS_ABIERTOS)->exists()) {
            PrestamoDomiciliario::create([
                'ejemplar_id' => $ejemplar->id,
                'socio_id' => $socio->id,
                'fecha_registro' => now()->subDays(8),
                'fecha_prestamo' => now()->subDays(8)->toDateString(),
                'fecha_vencimiento' => now()->addDays(7)->toDateString(),
                'estado' => PrestamoDomiciliario::ESTADO_ACTIVO,
                'registrado_por' => $registrador->id,
            ]);
        }

        $socioReservante = Socio::firstOrCreate(
            ['dni' => '20000007'],
            [
                'nombre_principal' => 'Emiliano Castro',
                'fecha_alta' => '2022-01-10',
                'estado' => 'activo',
                'tipo_socio_id' => $estandar->id,
            ]
        );

        if (! Reserva::where('libro_id', $libro->id)->where('socio_id', $socioReservante->id)->exists()) {
            Reserva::create([
                'libro_id' => $libro->id,
                'socio_id' => $socioReservante->id,
                'fecha_reserva' => now()->subDay()->toDateString(),
                'estado' => Reserva::ESTADO_PENDIENTE,
            ]);
        }
    }

    private function sembrarLibroReservableSinReservas(): void
    {
        $libro = Libro::firstOrCreate(['titulo' => 'Cocina de estación: recetas de otoño']);
        if ($libro->ejemplares()->count() === 0) {
            $libro->ejemplares()->create([
                'modalidad_acceso' => Ejemplar::MODALIDAD_LIBRE_CIRCULACION,
                'fecha_ingreso' => '2023-04-01',
                'origen' => Ejemplar::ORIGEN_DONACION,
            ]);
        }
    }

    private function sembrarReservaYaPersonalAlertado(TipoSocio $estandar): void
    {
        $socio = Socio::firstOrCreate(
            ['dni' => '20000008'],
            [
                'nombre_principal' => 'Patricia Weiss',
                'fecha_alta' => '2018-07-20',
                'estado' => 'activo',
                'tipo_socio_id' => $estandar->id,
            ]
        );

        $libro = Libro::firstOrCreate(['titulo' => 'Atlas histórico de la región']);
        $ejemplar = $libro->ejemplares()->first() ?? $libro->ejemplares()->create([
            'modalidad_acceso' => Ejemplar::MODALIDAD_LIBRE_CIRCULACION,
            'fecha_ingreso' => '2017-02-01',
            'origen' => Ejemplar::ORIGEN_COMPRA,
        ]);

        if (! Reserva::where('libro_id', $libro->id)->where('socio_id', $socio->id)->exists()) {
            $fechaAlerta = now()->subHours(6);

            Reserva::create([
                'libro_id' => $libro->id,
                'socio_id' => $socio->id,
                'fecha_reserva' => now()->subDays(4)->toDateString(),
                'estado' => Reserva::ESTADO_PERSONAL_ALERTADO,
                'fecha_alerta_al_personal' => $fechaAlerta,
                'fecha_limite_retiro' => Reserva::calcularFechaLimiteRetiro(
                    $fechaAlerta,
                    (int) \App\Models\ParametroConfiguracion::obtener(\App\Models\ParametroConfiguracion::VENTANA_RETIRO_RESERVA_HORAS, 48),
                    array_map('trim', explode(',', (string) \App\Models\ParametroConfiguracion::obtener(\App\Models\ParametroConfiguracion::DIAS_ATENCION_AL_PUBLICO, 'lunes,martes,miercoles,jueves,viernes')))
                ),
                'ejemplar_asignado_id' => $ejemplar->id,
            ]);
        }
    }
}
