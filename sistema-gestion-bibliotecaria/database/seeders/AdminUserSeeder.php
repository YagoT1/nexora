<?php

// Origen: Plan de Implementación v2, Módulo 1 (pre-checklist: al menos un usuario de cada rol
// para verificar autenticación). Este usuario es SOLO para staging/desarrollo — no debe
// ejecutarse este seeder contra producción con estas credenciales (ver docs/BOOTSTRAP.md).

namespace Database\Seeders;

use App\Models\User;
use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\Hash;

class AdminUserSeeder extends Seeder
{
    public function run(): void
    {
        if (app()->environment('production')) {
            return; // Nunca crear usuarios de prueba en producción.
        }

        User::firstOrCreate(
            ['email' => 'admin@biblioteca.test'],
            [
                'name' => 'Administrador de prueba',
                'password' => Hash::make('password'),
                'rol' => User::ROL_ADMINISTRADOR,
                'estado' => 'activo',
                'email_verified_at' => now(),
            ]
        );

        User::firstOrCreate(
            ['email' => 'personal@biblioteca.test'],
            [
                'name' => 'Personal de prueba',
                'password' => Hash::make('password'),
                'rol' => User::ROL_PERSONAL,
                'estado' => 'activo',
                'email_verified_at' => now(),
            ]
        );

        User::firstOrCreate(
            ['email' => 'voluntario@biblioteca.test'],
            [
                'name' => 'Voluntario de prueba',
                'password' => Hash::make('password'),
                'rol' => User::ROL_VOLUNTARIO,
                'estado' => 'activo',
                'email_verified_at' => now(),
            ]
        );
    }
}
