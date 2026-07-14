<?php

// Origen: Plan de Implementación v2, Módulo 2 — Catálogo, "CRUD de Autor, Editorial, Categoría".
// Ruta protegida por middleware role:administrador,personal (Modelo de Dominio v2, 6.1).

namespace App\Http\Controllers\Catalogo;

use App\Http\Controllers\Controller;
use App\Models\Editorial;
use Illuminate\Http\Request;

class EditorialController extends Controller
{
    public function index()
    {
        $editoriales = Editorial::withCount('libros')->orderBy('nombre')->paginate(20);

        return view('catalogo.editoriales.index', compact('editoriales'));
    }

    public function create()
    {
        return view('catalogo.editoriales.create');
    }

    public function store(Request $request)
    {
        $datos = $request->validate([
            'nombre' => ['required', 'string', 'max:255'],
        ]);

        Editorial::create($datos);

        return redirect()->route('catalogo.editoriales.index')->with('status', 'Editorial creada correctamente.');
    }

    public function edit(Editorial $editorial)
    {
        return view('catalogo.editoriales.edit', compact('editorial'));
    }

    public function update(Request $request, Editorial $editorial)
    {
        $datos = $request->validate([
            'nombre' => ['required', 'string', 'max:255'],
        ]);

        $editorial->update($datos);

        return redirect()->route('catalogo.editoriales.index')->with('status', 'Editorial actualizada correctamente.');
    }

    /**
     * Misma salvaguarda que AutorController::destroy(): no se permite eliminar una Editorial con
     * Libros asociados (relación hasMany, no M:N, pero el riesgo de dejar un vínculo roto es el
     * mismo).
     */
    public function destroy(Editorial $editorial)
    {
        if ($editorial->libros()->exists()) {
            return back()->with('status', 'No se puede eliminar: la editorial tiene libros asociados.');
        }

        $editorial->delete();

        return redirect()->route('catalogo.editoriales.index')->with('status', 'Editorial eliminada correctamente.');
    }
}
