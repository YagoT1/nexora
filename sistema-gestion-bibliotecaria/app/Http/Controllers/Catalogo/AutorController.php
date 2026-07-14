<?php

// Origen: Plan de Implementación v2, Módulo 2 — Catálogo, "CRUD de Autor, Editorial, Categoría".
// Ruta protegida por middleware role:administrador,personal (Modelo de Dominio v2, 6.1, tabla de
// permisos: "Gestionar catálogo y ejemplares" -> Administrador y Personal, no Voluntario).

namespace App\Http\Controllers\Catalogo;

use App\Http\Controllers\Controller;
use App\Models\Autor;
use Illuminate\Http\Request;

class AutorController extends Controller
{
    public function index()
    {
        $autores = Autor::withCount('libros')->orderBy('nombre')->paginate(20);

        return view('catalogo.autores.index', compact('autores'));
    }

    public function create()
    {
        return view('catalogo.autores.create');
    }

    public function store(Request $request)
    {
        $datos = $request->validate([
            'nombre' => ['required', 'string', 'max:255'],
            'notas' => ['nullable', 'string'],
        ]);

        Autor::create($datos);

        return redirect()->route('catalogo.autores.index')->with('status', 'Autor creado correctamente.');
    }

    public function edit(Autor $autor)
    {
        return view('catalogo.autores.edit', compact('autor'));
    }

    public function update(Request $request, Autor $autor)
    {
        $datos = $request->validate([
            'nombre' => ['required', 'string', 'max:255'],
            'notas' => ['nullable', 'string'],
        ]);

        $autor->update($datos);

        return redirect()->route('catalogo.autores.index')->with('status', 'Autor actualizado correctamente.');
    }

    /**
     * D-02 (separación Libro/Ejemplar) no impide eliminar un Autor, pero borrarlo mientras tiene
     * Libros asociados dejaría esos Libros con un vínculo M:N roto sin aviso. Se bloquea la
     * eliminación en ese caso en lugar de forzar un borrado en cascada silencioso.
     */
    public function destroy(Autor $autor)
    {
        if ($autor->libros()->exists()) {
            return back()->with('status', 'No se puede eliminar: el autor tiene libros asociados.');
        }

        $autor->delete();

        return redirect()->route('catalogo.autores.index')->with('status', 'Autor eliminado correctamente.');
    }
}
