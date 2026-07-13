<?php

// Origen: Propuesta de Arquitectura v2, DA-05. RN-14: "Este registro no puede ser eliminado
// por ningun usuario, incluido el Administrador." Por eso este modelo es deliberadamente
// append-only: no se exponen metodos update()/delete() de uso normal, y no usa SoftDeletes
// (no debe poder "eliminarse" ni siquiera de forma logica desde la aplicacion).

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class RegistroAuditoria extends Model
{
    public const UPDATED_AT = null; // no se actualiza: un registro de auditoria nunca se modifica.

    protected $table = 'registros_auditoria';

    protected $fillable = [
        'usuario_id',
        'accion',
        'entidad_type',
        'entidad_id',
        'valor_anterior',
        'valor_nuevo',
    ];

    protected function casts(): array
    {
        return [
            'valor_anterior' => 'array',
            'valor_nuevo' => 'array',
            'created_at' => 'datetime',
        ];
    }

    public function usuario()
    {
        return $this->belongsTo(User::class, 'usuario_id');
    }

    /**
     * Único punto de escritura permitido. No existe un método update() de negocio
     * ni se habilita eliminación desde controladores o comandos artisan.
     */
    public static function registrar(array $datos): self
    {
        return static::create($datos);
    }
}
