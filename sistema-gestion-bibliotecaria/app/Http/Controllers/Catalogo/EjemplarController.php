<?php

// Origen: Plan de Implementación v2, Módulo 2 — Catálogo, "CRUD de Ejemplar". Ruta anidada bajo
// Libro (catalogo.libros.ejemplares.*): un Ejemplar siempre existe en el contexto de un Libro. Sin
// 'index'/'show' propios: el listado de ejemplares de un Libro es responsabilidad de la vista de
// detalle de Libro (catalogo.libros.show, Paso 6).

namespace App\Http\Controllers\Catalogo;

use App\Http\Controllers\Controller;
use App\Http\Requests\Catalogo\EjemplarRequest;
use App\Models\Ejemplar;
use App\Models\Libro;
use App\Models\Reserva;

class EjemplarController extends Controller
{
    public function create(Libro $libro)
    {
        return view('catalogo.ejemplares.create', compact('libro'));
    }

    public function store(EjemplarRequest $request, Libro $libro)
    {
        $libro->ejemplares()->create($request->validated());

        return redirect()
            ->route('catalogo.libros.show', $libro)
            ->with('status', 'Ejemplar creado correctamente.');
    }

    public function edit(Libro $libro, Ejemplar $ejemplar)
    {
        $this->verificarPertenencia($libro, $ejemplar);

        return view('catalogo.ejemplares.edit', compact('libro', 'ejemplar'));
    }

    /**
     * Paso 7 del briefing (RN-21): si este cambio de modalidad deja reservas 'pendiente' del Libro
     * sin ningún ejemplar capaz de satisfacerlas, se alerta al personal en el mismo mensaje de
     * confirmación. RN-21 no exige bloquear el cambio ni cancelar las reservas automáticamente —
     * "el sistema alerta al personal para que cancele y gestione esas reservas manualmente": la
     * gestión de la reserva en sí es responsabilidad del Módulo 5, todavía no construido.
     */
    public function update(EjemplarRequest $request, Libro $libro, Ejemplar $ejemplar)
    {
        $this->verificarPertenencia($libro, $ejemplar);

        $modalidadAnterior = $ejemplar->modalidad_acceso;
        $ejemplar->update($request->validated());

        $mensaje = 'Ejemplar actualizado correctamente.';
        if ($ejemplar->modalidad_acceso !== $modalidadAnterior && $this->dejaReservasSinSatisfacer($libro)) {
            $mensaje .= ' Atención (RN-21): este libro tiene reservas pendientes y, tras este '
                .'cambio, ningún ejemplar puede satisfacerlas — gestione esas reservas manualmente.';
        }

        return redirect()
            ->route('catalogo.libros.show', $libro)
            ->with('status', $mensaje);
    }

    /**
     * Salvaguarda no derivada explícitamente de una RN/DA (mismo patrón que ADR-003 para Módulo 1):
     * no se permite eliminar un Ejemplar con un movimiento activo (RN-04, Ejemplar::tieneMovimientoActivo()),
     * para no borrar el registro de una copia física que en este momento está prestada, en custodia
     * externa o en movimiento interno.
     */
    public function destroy(Libro $libro, Ejemplar $ejemplar)
    {
        $this->verificarPertenencia($libro, $ejemplar);

        if ($ejemplar->tieneMovimientoActivo()) {
            return back()->with('status', 'No se puede eliminar: el ejemplar tiene un movimiento activo.');
        }

        $ejemplar->delete();

        return redirect()
            ->route('catalogo.libros.show', $libro)
            ->with('status', 'Ejemplar eliminado correctamente.');
    }

    /**
     * RN-21: el Libro tiene reservas en estado 'pendiente' (textual: "reservas Pendientes", no se
     * incluye 'personal_alertado' u otros estados — la regla habla puntualmente de pendientes) Y
     * ninguno de sus ejemplares puede salir de la biblioteca (Ejemplar::puedeSalirDeLaBiblioteca(),
     * RN-08/RN-09) para satisfacerlas.
     *
     * Origen: Módulo 2, Paso 7. Refactor de Módulo 5, Paso 1: el literal 'pendiente' se reemplaza
     * por Reserva::ESTADO_PENDIENTE (constante introducida en Módulo 5) — sin cambio de
     * comportamiento, mismo criterio de consistencia que otros refactors DRY del proyecto.
     */
    private function dejaReservasSinSatisfacer(Libro $libro): bool
    {
        if (! $libro->reservas()->where('estado', Reserva::ESTADO_PENDIENTE)->exists()) {
            return false;
        }

        return $libro->ejemplares
            ->doesntContain(fn (Ejemplar $candidato) => $candidato->puedeSalirDeLaBiblioteca());
    }

    /**
     * Verificación explícita de pertenencia en vez de depender del scoping automático de rutas
     * anidadas de Laravel: mismo criterio de cautela que llevó a fijar manualmente los nombres de
     * parámetro de las demás rutas de este módulo (ver phase-summary.md, Paso 2) — un mecanismo no
     * verificable en este entorno sin PHP/Composer reales (ADR-002).
     */
    private function verificarPertenencia(Libro $libro, Ejemplar $ejemplar): void
    {
        abort_unless($ejemplar->libro_id === $libro->id, 404);
    }
}
