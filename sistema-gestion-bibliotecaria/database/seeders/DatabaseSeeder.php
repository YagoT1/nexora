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
     */
    public function run(): void
    {
        $this->call([
            TipoSocioSeeder::class,
            ParametroConfiguracionSeeder::class,
            AdminUserSeeder::class,
        ]);
    }
}
