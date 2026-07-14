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
     */
    public function run(): void
    {
        $this->call([
            TipoSocioSeeder::class,
            ParametroConfiguracionSeeder::class,
            AdminUserSeeder::class,
            CatalogoDemoSeeder::class,
        ]);
    }
}
