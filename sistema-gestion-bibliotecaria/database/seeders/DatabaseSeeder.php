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
     */
    public function run(): void
    {
        $this->call([
            TipoSocioSeeder::class,
            ParametroConfiguracionSeeder::class,
            AdminUserSeeder::class,
            CatalogoDemoSeeder::class,
            SociosDemoSeeder::class,
            PrestamosDemoSeeder::class,
        ]);
    }
}
