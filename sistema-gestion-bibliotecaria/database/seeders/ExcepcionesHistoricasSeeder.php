<?php

// Origen: Plan de Implementación v2, Módulo 6 — Excepciones y restricciones, CU-5. Ver
// Fase 6 - Development/BRIEFING-MODULO-6-EXCEPCIONES-RESTRICCIONES.md, Paso 6, y Riesgos R-2/R-3.
//
// A diferencia de los *DemoSeeder (CatalogoDemoSeeder, SociosDemoSeeder, PrestamosDemoSeeder,
// RenovacionesReservasDemoSeeder — todos bloqueados explícitamente en producción), este seeder SÍ
// puede ejecutarse en producción (R-3): el caso histórico de excepción individual (relevamiento
// 7.1/7.2) es una decisión real ya tomada por la Comisión Directiva, no un dato ficticio de
// demostración. Es idempotente (firstOrCreate en cada paso), por lo que puede correr en cada
// despliegue sin duplicar registros.
//
// Nota: el caso 7.1 (socios honorarios) NO requiere ninguna acción de este seeder — ya está
// cubierto desde el Módulo 1 vía TipoSocioSeeder (TipoSocio "Honorario" con
// sujeto_a_restriccion_automatica = false). Solo el caso 7.2 (excepción individual de penalización)
// requiere crear un registro nuevo de ExcepcionAutorizada.
//
// LIMITACIÓN DOCUMENTADA (Riesgo R-2): el dominio no tiene ningún campo de "código de socio" — el
// identificador "S-0072" citado en el plan de Fase 3 es un código informal del relevamiento en
// papel, no una columna de este sistema. Este seeder representa el caso con un Socio de ejemplo
// identificable por un DNI placeholder ('HIST-S-0072') y por su motivo. ANTES DE CORRER ESTE
// SEEDER CONTRA DATOS REALES DE PRODUCCIÓN, la Comisión Directiva/Administrador debe:
//   (a) identificar al socio real que corresponde al caso histórico, y
//   (b) reemplazar el valor de $dniPlaceholder más abajo por el DNI real de ese socio (para que
//       este seeder reutilice ese registro existente en vez de crear uno nuevo), o bien cargar la
//       excepción manualmente desde la UI (ruta 'excepciones.create') sobre el socio real y no
//       ejecutar este seeder.
// Ver docs/REVISION-MODULO-6.md para el detalle de este procedimiento.

namespace Database\Seeders;

use App\Models\ExcepcionAutorizada;
use App\Models\Socio;
use App\Models\TipoSocio;
use App\Models\User;
use Illuminate\Database\Seeder;

class ExcepcionesHistoricasSeeder extends Seeder
{
    public function run(): void
    {
        // RN-11: autorizado_por es obligatorio (no nullable, ver migración
        // 2024_01_01_000200_create_excepciones_autorizadas_table). AdminUserSeeder está bloqueado
        // en producción (igual que los *DemoSeeder), así que en un despliegue real recién puede
        // haber un Administrador después de que la Comisión Directiva dé de alta el primero desde
        // el panel (Módulo 1). Si todavía no existe ninguno, no hay quién "autorice" esta excepción
        // histórica — se sale sin crear nada; el seeder es idempotente y puede volver a correrse.
        $administrador = User::where('rol', User::ROL_ADMINISTRADOR)
            ->where('estado', 'activo')
            ->first();

        if (! $administrador) {
            return;
        }

        $tipoSocioEstandar = TipoSocio::firstOrCreate(
            ['nombre' => 'Estándar'],
            ['limite_prestamos_simultaneos' => 3, 'sujeto_a_restriccion_automatica' => true]
        );

        // Placeholder documentado (Riesgo R-2) — ver el comentario de cabecera de este archivo
        // antes de correr este seeder contra datos reales de producción.
        $dniPlaceholder = 'HIST-S-0072';

        $socioHistorico = Socio::firstOrCreate(
            ['dni' => $dniPlaceholder],
            [
                'nombre_principal' => 'Socio histórico (S-0072 — ver docs/REVISION-MODULO-6.md)',
                'fecha_alta' => now()->toDateString(),
                'estado' => 'activo',
                'tipo_socio_id' => $tipoSocioEstandar->id,
            ]
        );

        // Relevamiento 7.2: "excepción histórica de penalización ... se mantiene vigente ... con
        // vigencia indefinida hasta nueva resolución" -> fecha_fin null (RN-11).
        ExcepcionAutorizada::firstOrCreate(
            [
                'entidad_afectada_type' => Socio::class,
                'entidad_afectada_id' => $socioHistorico->id,
                'tipo' => ExcepcionAutorizada::TIPO_EXENCION_RESTRICCION,
            ],
            [
                'autorizado_por' => $administrador->id,
                'fecha_autorizacion' => now(),
                'motivo' => 'Colaboración histórica con la institución',
                'fecha_inicio' => now()->toDateString(),
                'fecha_fin' => null,
                'estado' => ExcepcionAutorizada::ESTADO_VIGENTE,
            ]
        );
    }
}
