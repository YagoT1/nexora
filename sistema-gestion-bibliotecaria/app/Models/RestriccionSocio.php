<?php

// Origen: Modelo de Dominio v2, 4.2. RN-06, RN-07, RN-18. Auditado desde el Módulo 6 (Decisión
// D-17): hasta entonces todas sus filas eran generadas por el sistema (Módulo 4); este módulo
// introduce la primera vía de creación humana (restricción manual por Personal/Administrador).

namespace App\Models;

use App\Support\Auditing\Auditable;
use Illuminate\Database\Eloquent\Model;

class RestriccionSocio extends Model
{
    use Auditable;

    protected $table = 'restricciones_socio';

    // Origen: Módulo 6, Decisión D-16. Formaliza como constantes el string libre `tipo` que ya
    // usaban PrestamoController::devolver() (Módulo 4) y los seeders de demostración.
    public const TIPO_AUTOMATICA = 'automatica';
    public const TIPO_MANUAL = 'manual';

    protected $fillable = [
        'socio_id', 'tipo', 'fecha_inicio', 'fecha_fin', 'dias_atraso_origen',
        'prestamo_domiciliario_id', 'generada_por_usuario_id', 'observaciones',
    ];

    protected function casts(): array
    {
        return ['fecha_inicio' => 'date', 'fecha_fin' => 'date'];
    }

    public function socio()
    {
        return $this->belongsTo(Socio::class);
    }

    // Origen: Módulo 6, Paso 1. Faltaba pese a que la columna `generada_por_usuario_id` (nullable
    // = generada por el sistema) ya existía desde el Módulo 1 — mismo criterio que
    // ExcepcionAutorizada::autorizadoPor()/revocadoPor(), necesaria para el listado de
    // restricciones activas/históricas por socio (Paso 4).
    public function generadaPor()
    {
        return $this->belongsTo(User::class, 'generada_por_usuario_id');
    }

    public function estaActiva(): bool
    {
        return $this->fecha_fin->isFuture() || $this->fecha_fin->isToday();
    }
}
