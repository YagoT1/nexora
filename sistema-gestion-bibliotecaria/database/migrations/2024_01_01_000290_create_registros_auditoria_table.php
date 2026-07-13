<?php

// Origen: Propuesta de Arquitectura v2, DA-05 "Registro de auditoría". RN-14.
// Append-only: no existe UPDATE ni DELETE previstos por el ORM sobre esta tabla (ver App\Models\RegistroAuditoria
// y App\Support\Auditing\Auditable). Retención mínima de 2 años (DA-05) — no se implementa purga automática
// en el Módulo 1; queda para una decisión operativa posterior si la retención debe limitarse.

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('registros_auditoria', function (Blueprint $table) {
            $table->id();
            $table->foreignId('usuario_id')->nullable()->constrained('users')->nullOnDelete();
            $table->string('accion'); // ej: 'parametro_configuracion.actualizado', 'socio.creado', 'excepcion.revocada'
            $table->string('entidad_type')->nullable();
            $table->unsignedBigInteger('entidad_id')->nullable();
            $table->jsonb('valor_anterior')->nullable();
            $table->jsonb('valor_nuevo')->nullable();
            $table->timestamp('created_at');

            $table->index(['entidad_type', 'entidad_id']);
            $table->index('usuario_id');
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('registros_auditoria');
    }
};
