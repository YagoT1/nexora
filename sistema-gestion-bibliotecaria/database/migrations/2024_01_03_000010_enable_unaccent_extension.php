<?php

// Origen: Módulo 3 — Socios, Paso 3 (ver Fase 6 - Development/BRIEFING-MODULO-3-SOCIOS.md, riesgo
// R-1). La búsqueda de socios exige ser tolerante a acentos (criterio de aceptación: buscar
// "Garcia" debe encontrar "García"), no solo a mayúsculas/minúsculas. `unaccent` es una extensión
// estándar de PostgreSQL (contrib), incluida en toda instalación de PostgreSQL 16 sin necesidad de
// paquetes adicionales — solo requiere habilitarse una vez por base de datos.

use Illuminate\Database\Migrations\Migration;
use Illuminate\Support\Facades\DB;

return new class extends Migration
{
    public function up(): void
    {
        DB::statement('CREATE EXTENSION IF NOT EXISTS unaccent');
    }

    public function down(): void
    {
        DB::statement('DROP EXTENSION IF EXISTS unaccent');
    }
};
