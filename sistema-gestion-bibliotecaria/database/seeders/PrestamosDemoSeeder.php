<?php

// Origen: preparación de revisión funcional del Módulo 4 — Préstamos y devoluciones. Mismo
// criterio que CatalogoDemoSeeder/SociosDemoSeeder: datos ficticios, idempotentes, solo para
// desarrollo/staging (nunca en producción).
//
// A diferencia de SociosDemoSeeder (Módulo 3), que ya dejaba préstamos DEVUELTOS con atraso para
// mostrar el historial, este seeder deja préstamos ACTIVOS y vencidos sin devolver todavía, para
// poder ejercitar en vivo el flujo de devolución (Paso 3) y ver sus efectos (RN-18, RN-07, alerta
// de reserva) ocurrir en el momento de la revisión, no ya consumados.
//
// Casos cubiertos deliberadamente:
// - Un socio Estándar con un préstamo activo vencido hace 5 días, y una Reserva pendiente sobre el
//   mismo libro de otro socio — al devolverlo desde la UI, debe generarse una RestriccionSocio de 5
//   días (RN-18) Y la alerta de reserva pendiente (criterio de aceptación explícito del módulo).
// - Un socio Honorario con un préstamo activo vencido hace 4 días — al devolverlo, NO debe
//   generarse ninguna restricción (RN-07), aunque el atraso se registre igual en el historial.
// - Un socio con una RestriccionSocio vigente Y una ExcepcionAutorizada de tipo "Exención" también
//   vigente — permite demostrar RN-06 registrando un préstamo nuevo pese a la restricción (R-2 del
//   briefing: no existe todavía ninguna interfaz para crear esta excepción, Módulo 6, así que se
//   siembra directamente).
// - Un ejemplar con modalidad "Restringido a autorización" y una ExcepcionAutorizada vigente de
//   tipo "Autorización de salida de material restringido" para ESE ejemplar — permite demostrar
//   RN-09 (mismo motivo que el punto anterior).

namespace Database\Seeders;

use App\Models\Ejemplar;
use App\Models\ExcepcionAutorizada;
use App\Models\Libro;
use App\Models\PrestamoDomiciliario;
use App\Models\Reserva;
use App\Models\RestriccionSocio;
use App\Models\Socio;
use App\Models\TipoSocio;
use App\Models\User;
use Illuminate\Database\Seeder;

class PrestamosDemoSeeder extends Seeder
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
        $honorario = TipoSocio::firstOrCreate(
            ['nombre' => 'Honorario'],
            ['limite_prestamos_simultaneos' => 5, 'sujeto_a_restriccion_automatica' => false]
        );

        $this->sembrarPrestamoConReservaPendiente($estandar, $registrador);
        $this->sembrarPrestamoHonorarioVencido($honorario, $registrador);
        $this->sembrarSocioConRestriccionYExcepcionDeExencion($estandar, $registrador);
        $this->sembrarEjemplarRestringidoConExcepcionVigente($registrador);
    }

    private function sembrarPrestamoConReservaPendiente(TipoSocio $estandar, User $registrador): void
    {
        $socio = Socio::firstOrCreate(
            ['dni' => '20000001'],
            [
                'nombre_principal' => 'Carlos Gómez',
                'fecha_alta' => '2022-05-01',
                'estado' => 'activo',
                'tipo_socio_id' => $estandar->id,
            ]
        );

        $libro = Libro::firstOrCreate(['titulo' => 'Compendio de historia local']);
        $ejemplar = $libro->ejemplares()->first() ?? $libro->ejemplares()->create([
            'modalidad_acceso' => Ejemplar::MODALIDAD_LIBRE_CIRCULACION,
            'fecha_ingreso' => '2021-01-01',
            'origen' => Ejemplar::ORIGEN_COMPRA,
        ]);

        if (! $ejemplar->prestamosDomiciliarios()->whereIn('estado', PrestamoDomiciliario::ESTADOS_ABIERTOS)->exists()) {
            PrestamoDomiciliario::create([
                'ejemplar_id' => $ejemplar->id,
                'socio_id' => $socio->id,
                'fecha_registro' => now()->subDays(20),
                'fecha_prestamo' => now()->subDays(20)->toDateString(),
                'fecha_vencimiento' => now()->subDays(5)->toDateString(),
                'estado' => PrestamoDomiciliario::ESTADO_ACTIVO,
                'registrado_por' => $registrador->id,
            ]);
        }

        $otroSocio = Socio::firstOrCreate(
            ['dni' => '20000002'],
            [
                'nombre_principal' => 'Lucía Fernández',
                'fecha_alta' => '2023-02-10',
                'estado' => 'activo',
                'tipo_socio_id' => $estandar->id,
            ]
        );

        if (! Reserva::where('libro_id', $libro->id)->where('socio_id', $otroSocio->id)->exists()) {
            Reserva::create([
                'libro_id' => $libro->id,
                'socio_id' => $otroSocio->id,
                'fecha_reserva' => now()->subDays(2)->toDateString(),
                'estado' => 'pendiente',
            ]);
        }
    }

    private function sembrarPrestamoHonorarioVencido(TipoSocio $honorario, User $registrador): void
    {
        $socio = Socio::firstOrCreate(
            ['dni' => '20000003'],
            [
                'nombre_principal' => 'Marta Ibarra',
                'fecha_alta' => '2017-06-01',
                'estado' => 'activo',
                'tipo_socio_id' => $honorario->id,
            ]
        );

        $libro = Libro::firstOrCreate(['titulo' => 'Guía de aves autóctonas']);
        $ejemplar = $libro->ejemplares()->first() ?? $libro->ejemplares()->create([
            'modalidad_acceso' => Ejemplar::MODALIDAD_LIBRE_CIRCULACION,
            'fecha_ingreso' => '2019-03-01',
            'origen' => Ejemplar::ORIGEN_DONACION,
        ]);

        if (! $ejemplar->prestamosDomiciliarios()->whereIn('estado', PrestamoDomiciliario::ESTADOS_ABIERTOS)->exists()) {
            PrestamoDomiciliario::create([
                'ejemplar_id' => $ejemplar->id,
                'socio_id' => $socio->id,
                'fecha_registro' => now()->subDays(19),
                'fecha_prestamo' => now()->subDays(19)->toDateString(),
                'fecha_vencimiento' => now()->subDays(4)->toDateString(),
                'estado' => PrestamoDomiciliario::ESTADO_ACTIVO,
                'registrado_por' => $registrador->id,
            ]);
        }
    }

    private function sembrarSocioConRestriccionYExcepcionDeExencion(TipoSocio $estandar, User $registrador): void
    {
        $socio = Socio::firstOrCreate(
            ['dni' => '20000004'],
            [
                'nombre_principal' => 'Diego Paredes',
                'fecha_alta' => '2020-09-01',
                'estado' => 'activo',
                'tipo_socio_id' => $estandar->id,
            ]
        );

        if (! $socio->restricciones()->exists()) {
            RestriccionSocio::create([
                'socio_id' => $socio->id,
                'tipo' => 'automatica',
                'fecha_inicio' => now()->subDays(2)->toDateString(),
                'fecha_fin' => now()->addDays(3)->toDateString(),
                'dias_atraso_origen' => 5,
            ]);
        }

        $yaTieneExcepcion = ExcepcionAutorizada::where('entidad_afectada_type', Socio::class)
            ->where('entidad_afectada_id', $socio->id)
            ->where('tipo', ExcepcionAutorizada::TIPO_EXENCION_RESTRICCION)
            ->exists();

        if (! $yaTieneExcepcion) {
            ExcepcionAutorizada::create([
                'tipo' => ExcepcionAutorizada::TIPO_EXENCION_RESTRICCION,
                'entidad_afectada_type' => Socio::class,
                'entidad_afectada_id' => $socio->id,
                'autorizado_por' => $registrador->id,
                'fecha_autorizacion' => now()->subDays(1)->toDateString(),
                'motivo' => 'Situación particular autorizada por la Comisión Directiva (dato de demostración).',
                'fecha_inicio' => now()->subDays(1)->toDateString(),
                'fecha_fin' => now()->addDays(10)->toDateString(),
                'estado' => 'vigente',
            ]);
        }
    }

    private function sembrarEjemplarRestringidoConExcepcionVigente(User $registrador): void
    {
        $libro = Libro::firstOrCreate(['titulo' => 'Archivo fotográfico institucional (restringido)']);
        $ejemplar = $libro->ejemplares()->first() ?? $libro->ejemplares()->create([
            'modalidad_acceso' => Ejemplar::MODALIDAD_RESTRINGIDO,
            'fecha_ingreso' => '2015-01-01',
            'origen' => Ejemplar::ORIGEN_DONACION,
        ]);

        $yaTieneExcepcion = ExcepcionAutorizada::where('entidad_afectada_type', Ejemplar::class)
            ->where('entidad_afectada_id', $ejemplar->id)
            ->where('tipo', ExcepcionAutorizada::TIPO_AUTORIZACION_MATERIAL_RESTRINGIDO)
            ->exists();

        if (! $yaTieneExcepcion) {
            ExcepcionAutorizada::create([
                'tipo' => ExcepcionAutorizada::TIPO_AUTORIZACION_MATERIAL_RESTRINGIDO,
                'entidad_afectada_type' => Ejemplar::class,
                'entidad_afectada_id' => $ejemplar->id,
                'autorizado_por' => $registrador->id,
                'fecha_autorizacion' => now()->subDays(1)->toDateString(),
                'motivo' => 'Investigador externo autorizado (dato de demostración).',
                'fecha_inicio' => now()->subDays(1)->toDateString(),
                'fecha_fin' => now()->addDays(10)->toDateString(),
                'estado' => 'vigente',
            ]);
        }
    }
}
