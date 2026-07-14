<?php

// Origen: Modelo de Dominio v2, 1.1 "Libro".

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class Libro extends Model
{
    protected $table = 'libros';

    protected $fillable = [
        'titulo', 'isbn', 'anio_publicacion', 'edicion', 'idioma', 'descripcion', 'editorial_id',
    ];

    public function editorial()
    {
        return $this->belongsTo(Editorial::class);
    }

    public function autores()
    {
        return $this->belongsToMany(Autor::class, 'libro_autor');
    }

    public function categorias()
    {
        return $this->belongsToMany(Categoria::class, 'libro_categoria');
    }

    public function ejemplares()
    {
        return $this->hasMany(Ejemplar::class);
    }

    // Origen: Modelo de Dominio v2, 3.3 "Reserva" (Reserva::libro() ya existe como belongsTo desde
    // el Módulo 1). Se agrega la inversa acá porque el Paso 7 (RN-21) necesita consultar, desde un
    // Libro, si tiene reservas en estado 'pendiente' sin tocar el modelo Reserva (Módulo 5).
    public function reservas()
    {
        return $this->hasMany(Reserva::class);
    }

    /**
     * Origen: Plan de Implementación v2, Módulo 2 — Catálogo, "Búsqueda de catálogo: ... estado".
     * Filtra libros con al menos un ejemplar en el estado operativo indicado (D-09: el estado no es
     * una columna, es derivado — ver Ejemplar::estadoActual()).
     *
     * ADVERTENCIA DE MANTENIMIENTO: este scope reproduce, como condiciones SQL, la misma lógica de
     * negocio que Ejemplar::estadoActual() expresa en PHP sobre una instancia ya cargada. No hay
     * forma de reutilizar directamente estadoActual() en una cláusula WHERE (no es una columna ni
     * una expresión SQL), así que la lógica queda deliberadamente duplicada en dos lugares. Si se
     * modifica estadoActual() (por ejemplo, al incorporar los Módulos 4/5 con reglas nuevas de
     * circulación), este scope debe revisarse en el mismo cambio para no divergir.
     */
    public function scopeConEstado($query, string $estado)
    {
        return $query->whereHas('ejemplares', function ($ejemplar) use ($estado) {
            match ($estado) {
                Ejemplar::ESTADO_MANUAL_EN_REPARACION, Ejemplar::ESTADO_MANUAL_EXTRAVIADO => $ejemplar
                    ->where('estado_manual', $estado),
                Ejemplar::ESTADO_PRESTADO => $ejemplar
                    ->whereNull('estado_manual')
                    ->whereHas('prestamosDomiciliarios', fn ($q) => $q->whereIn('estado', ['activo', 'atrasado'])),
                // Corrección 2026-07-14 (ver ADR-012, actualización de segunda ejecución):
                // wherePivotNull() NO es válido dentro de un closure de whereHas() — ese closure
                // recibe un Builder acotado al modelo relacionado (MovimientoInterno/CustodiaExterna),
                // no la instancia de la relación BelongsToMany, así que wherePivotNull() (que solo
                // existe en BelongsToMany) cae en el resolutor dinámico "where<Columna>" de Eloquent
                // y genera SQL inválido. La tabla pivote sí queda unida (join) dentro de ese
                // whereHas(), así que se referencia su columna calificada directamente.
                Ejemplar::ESTADO_EN_MOVIMIENTO_INTERNO => $ejemplar
                    ->whereNull('estado_manual')
                    ->whereHas('movimientosInternos', fn ($q) => $q->whereNull('ejemplares_movimiento_interno.fecha_retorno_efectiva')),
                Ejemplar::ESTADO_EN_CUSTODIA_EXTERNA => $ejemplar
                    ->whereNull('estado_manual')
                    ->whereHas('custodiasExternas', fn ($q) => $q->whereNull('ejemplares_custodia_externa.fecha_retorno_efectiva')),
                Ejemplar::ESTADO_DISPONIBLE => $ejemplar
                    ->whereNull('estado_manual')
                    ->whereDoesntHave('prestamosDomiciliarios', fn ($q) => $q->whereIn('estado', ['activo', 'atrasado']))
                    ->whereDoesntHave('movimientosInternos', fn ($q) => $q->whereNull('ejemplares_movimiento_interno.fecha_retorno_efectiva'))
                    ->whereDoesntHave('custodiasExternas', fn ($q) => $q->whereNull('ejemplares_custodia_externa.fecha_retorno_efectiva')),
                // Valor no reconocido: no debe devolver falsos positivos.
                default => $ejemplar->whereRaw('1 = 0'),
            };
        });
    }
}
