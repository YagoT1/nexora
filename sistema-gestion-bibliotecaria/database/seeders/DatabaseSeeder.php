<?php

namespace Database\Seeders;

use Illuminate\Database\Seeder;

class DatabaseSeeder extends Seeder
{
    /**
     * Seed the application's database.
     *
     * Módulo 1: reemplaza el usuario genérico de Breeze por los seeders del dominio
     * (ver sistema-gestion-bibliotecaria/database/seeders/DatabaseSeeder.php, docs/BOOTSTRAP.md paso 3).
     * AdminUserSeeder ya crea los usuarios de prueba de cada rol con rol/estado válidos.
     *
     * Módulo 2 (Catálogo): CatalogoDemoSeeder agrega datos de ejemplo para la revisión funcional
     * de los Pasos 1 a 4 (Autor, Editorial, Categoría, Libro, Ejemplar) — ver
     * docs/REVISION-MODULO-2.md.
     *
     * Módulo 3 (Socios): SociosDemoSeeder agrega datos de ejemplo para la revisión funcional de
     * la búsqueda tolerante a acentos y la vista de mostrador (atraso, contador de atrasos,
     * RN-07 para socios Honorario) — ver docs/REVISION-MODULO-3.md. Corre después de
     * CatalogoDemoSeeder porque reutiliza el mismo usuario Administrador como registrador de los
     * préstamos de demostración.
     *
     * Módulo 4 (Préstamos y devoluciones): PrestamosDemoSeeder deja préstamos ACTIVOS y vencidos
     * (a diferencia de los ya devueltos de SociosDemoSeeder), para poder ejercitar en vivo el flujo
     * de devolución y ver sus efectos (RN-18, RN-07, alerta de reserva) ocurrir en el momento de la
     * revisión — ver docs/REVISION-MODULO-4.md. Corre al final porque reutiliza los Tipos de Socio
     * ya sembrados y al usuario Administrador como registrador.
     *
     * Módulo 5 (Renovaciones y reservas): RenovacionesReservasDemoSeeder agrega un préstamo
     * renovable sin reservas, uno bloqueado por una reserva pendiente ajena (RN-03), un libro
     * reservable sin reservas todavía, y una reserva ya en 'personal_alertado' con su fecha límite
     * de retiro ya calculada (RN-05/D-13) — ver docs/REVISION-MODULO-5.md. Corre al final, después
     * de PrestamosDemoSeeder, por el mismo motivo (reutiliza Tipos de Socio y Administrador).
     *
     * Módulo 6 (Excepciones y restricciones): ExcepcionesHistoricasSeeder, a diferencia de todos
     * los *DemoSeeder de arriba, SÍ puede correr en producción (Riesgo R-3 del briefing) — migra el
     * caso histórico real del relevamiento (7.2), no datos ficticios. Se ubica junto a
     * TipoSocioSeeder/ParametroConfiguracionSeeder (production-safe) en vez de junto a los
     * *DemoSeeder. Depende de que ya exista un Administrador activo (creado por AdminUserSeeder en
     * desarrollo, o dado de alta manualmente en un despliegue real) — si no hay ninguno todavía, no
     * crea nada y puede volver a correrse más adelante (ver docs/REVISION-MODULO-6.md).
     */
    public function run(): void
    {
        $this->call([
            TipoSocioSeeder::class,
            ParametroConfiguracionSeeder::class,
            AdminUserSeeder::class,
            ExcepcionesHistoricasSeeder::class,
            CatalogoDemoSeeder::class,
            SociosDemoSeeder::class,
            PrestamosDemoSeeder::class,
            RenovacionesReservasDemoSeeder::class,
        ]);
    }
}
