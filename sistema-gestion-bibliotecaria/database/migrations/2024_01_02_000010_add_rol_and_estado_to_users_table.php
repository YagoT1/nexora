<?php

// Origen: Modelo de Dominio v2, 6.1 "Usuario". DA-05 (roles Administrador/Personal/Voluntario).
// Se agrega sobre la migracion 'users' que genera Laravel/Breeze (0001_01_01_000000_create_users_table.php),
// en lugar de editarla, para no depender de modificar un archivo generado por el instalador.

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('users', function (Blueprint $table) {
            // administrador | personal | voluntario
            $table->string('rol')->default('voluntario')->after('email');
            $table->string('estado')->default('activo')->after('rol'); // activo | inactivo
        });
    }

    public function down(): void
    {
        Schema::table('users', function (Blueprint $table) {
            $table->dropColumn(['rol', 'estado']);
        });
    }
};
