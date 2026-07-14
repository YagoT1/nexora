<?php

// Origen: Plan de Implementación v2, Módulo 2 — Catálogo, "CRUD de Ejemplar". Ruta anidada bajo
// Libro (catalogo.libros.ejemplares.*): un Ejemplar siempre existe en el contexto de un Libro. Sin
// 'index'/'show' propios: el listado de ejemplares de un Libro es responsabilidad de la vista de
// detalle de Libro (Paso 6 del briefing), que todavía no existe.

namespace App\Http\Controllers\Catalogo;

use App\Http\Controllers\Controller;
use App\Http\Requests\Catalogo\EjemplarRequest;
use App\Models\Ejemplar;
use App\Models\Libro;

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
            ->route('catalogo.libros.edit', $libro)
            ->with('status', 'Ejemplar creado correctamente.');
    }

    public function edit(Libro $libro, Ejemplar $ejemplar)
    {
        $this->verificarPertenencia($libro, $ejemplar);

        return view('catalogo.ejemplares.edit', compact('libro', 'ejemplar'));
    }

    public function update(EjemplarRequest $request, Libro $libro, Ejemplar $ejemplar)
    {
        $this->verificarPertenencia($libro, $ejemplar);

        $ejemplar->update($request->validated());

        return redirect()
            ->route('catalogo.libros.edit', $libro)
            ->with('status', 'Ejemplar actualizado correctamente.');
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
            ->route('catalogo.libros.edit', $libro)
            ->with('status', 'Ejemplar eliminado correctamente.');
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
