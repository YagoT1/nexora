<?php

// Origen: Modelo de Dominio v2, 2.1 y 6.2 (valores iniciales de parámetros).

namespace Database\Seeders;

use App\Models\TipoSocio;
use Illuminate\Database\Seeder;

class TipoSocioSeeder extends Seeder
{
    public function run(): void
    {
        TipoSocio::firstOrCreate(
            ['nombre' => 'Estándar'],
            ['limite_prestamos_simultaneos' => 3, 'sujeto_a_restriccion_automatica' => true]
        );

        TipoSocio::firstOrCreate(
            ['nombre' => 'Honorario'],
            ['limite_prestamos_simultaneos' => 5, 'sujeto_a_restriccion_automatica' => false]
        );
    }
}
