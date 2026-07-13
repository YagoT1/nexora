<?php

// Origen: Modelo de Dominio v2, 6.2. D-04 (configurable sin intervención técnica). RN-14 (auditado).

namespace App\Models;

use App\Support\Auditing\Auditable;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Support\Facades\Cache;

class ParametroConfiguracion extends Model
{
    use Auditable;

    protected $table = 'parametros_configuracion';

    protected $fillable = ['clave', 'valor', 'descripcion'];

    // Claves definidas en Modelo de Dominio v2, 6.2.
    public const LIMITE_PRESTAMOS_ESTANDAR = 'limite_prestamos_estandar';
    public const LIMITE_PRESTAMOS_HONORARIO = 'limite_prestamos_honorario';
    public const PLAZO_PRESTAMO_DIAS = 'plazo_prestamo_dias';
    public const VENTANA_RETIRO_RESERVA_HORAS = 'ventana_retiro_reserva_horas_atencion';
    public const DIAS_ATENCION_AL_PUBLICO = 'dias_atencion_al_publico';
    public const TOPE_MAXIMO_RESTRICCION_DIAS = 'tope_maximo_restriccion_dias';

    public static function obtener(string $clave, mixed $default = null): mixed
    {
        return Cache::remember("parametro_configuracion.$clave", 3600, function () use ($clave, $default) {
            return static::where('clave', $clave)->value('valor') ?? $default;
        });
    }

    protected static function booted(): void
    {
        static::saved(function (self $parametro) {
            Cache::forget("parametro_configuracion.{$parametro->clave}");
        });
    }
}
