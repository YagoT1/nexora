<?php

// Origen: Propuesta de Arquitectura v2, DA-05 "Registro de auditoría". RN-14.
// Trait que se agrega a los modelos cuyas modificaciones deben quedar auditadas
// (ParametroConfiguracion, Socio, ExcepcionAutorizada, User, etc.). Registra automaticamente
// creacion/actualizacion/eliminacion en registros_auditoria con valor anterior y nuevo.
// El registro es append-only: RegistroAuditoria no expone metodos de actualizacion ni borrado (ver ese modelo).
//
// Salvaguarda agregada tras revisión de código (no deriva de RN/DA existente, ver
// eos-benchmark/Fase 6 - Development/ADR-003-...): los atributos declarados en $hidden del
// modelo (p. ej. User::$hidden = ['password', 'remember_token']) se excluyen del payload
// auditado. Sin este filtro, cada alta/baja/modificación de un usuario quedaba escrita en
// registros_auditoria con el hash de la contraseña, ampliando innecesariamente la superficie
// de exposición de esa tabla.

namespace App\Support\Auditing;

use App\Models\RegistroAuditoria;
use Illuminate\Support\Arr;
use Illuminate\Support\Facades\Auth;

trait Auditable
{
    public static function bootAuditable(): void
    {
        static::created(function ($model) {
            static::registrarAuditoria($model, 'creado', null, $model->getAttributes());
        });

        static::updated(function ($model) {
            static::registrarAuditoria(
                $model,
                'actualizado',
                $model->getOriginal(),
                $model->getChanges()
            );
        });

        static::deleted(function ($model) {
            static::registrarAuditoria($model, 'eliminado', $model->getOriginal(), null);
        });
    }

    protected static function registrarAuditoria($model, string $accion, ?array $anterior, ?array $nuevo): void
    {
        $entidad = class_basename($model);
        $camposOcultos = $model->getHidden();

        RegistroAuditoria::registrar([
            'usuario_id' => Auth::id(),
            'accion' => mb_strtolower($entidad) . '.' . $accion,
            'entidad_type' => get_class($model),
            'entidad_id' => $model->getKey(),
            'valor_anterior' => $anterior ? Arr::except($anterior, $camposOcultos) : null,
            'valor_nuevo' => $nuevo ? Arr::except($nuevo, $camposOcultos) : null,
        ]);
    }
}
