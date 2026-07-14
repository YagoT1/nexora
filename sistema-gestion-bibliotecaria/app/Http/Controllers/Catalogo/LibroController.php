<?php

// Origen: Plan de Implementación v2, Módulo 2 — Catálogo, "CRUD de Libro: título, ISBN (no único,
// no obligatorio), año, edición, idioma, descripción, autores (relación M:N), editorial,
// categorías". Ruta protegida por middleware role:administrador,personal (Modelo de Dominio v2, 6.1).
//
// 'show' queda deliberadamente fuera de este paso: la vista de detalle de Libro (Paso 6 del
// briefing) necesita listar los ejemplares del libro con su estado actual, y Ejemplar (Paso 4)
// todavía no existe. Se habilita cuando corresponda, en el Paso 6.

namespace App\Http\Controllers\Catalogo;

use App\Http\Controllers\Controller;
use App\Http\Requests\Catalogo\LibroRequest;
use App\Models\Autor;
use App\Models\Categoria;
use App\Models\Editorial;
use App\Models\Libro;

class LibroController extends Controller
{
    public function index()
    {
        $libros = Libro::with(['autores', 'editorial'])->orderBy('titulo')->paginate(20);

        return view('catalogo.libros.index', compact('libros'));
    }

    public function create()
    {
        return view('catalogo.libros.create', $this->datosDeApoyoParaFormulario());
    }

    public function store(LibroRequest $request)
    {
        $datos = $request->validated();

        $libro = Libro::create($this->camposPropios($datos));
        $libro->autores()->sync($datos['autores'] ?? []);
        $libro->categorias()->sync($datos['categorias'] ?? []);

        return redirect()->route('catalogo.libros.index')->with('status', 'Libro creado correctamente.');
    }

    public function edit(Libro $libro)
    {
        $libro->load('autores', 'categorias');

        return view('catalogo.libros.edit', array_merge(
            $this->datosDeApoyoParaFormulario(),
            ['libro' => $libro]
        ));
    }

    public function update(LibroRequest $request, Libro $libro)
    {
        $datos = $request->validated();

        $libro->update($this->camposPropios($datos));
        $libro->autores()->sync($datos['autores'] ?? []);
        $libro->categorias()->sync($datos['categorias'] ?? []);

        return redirect()->route('catalogo.libros.index')->with('status', 'Libro actualizado correctamente.');
    }

    /**
     * No se permite eliminar un Libro con Ejemplares asociados (D-02: el Libro es la obra, el
     * Ejemplar la copia física — borrar el Libro dejaría Ejemplares sin obra que los describa).
     * Las filas de los pivotes libro_autor/libro_categoria sí se eliminan en cascada a nivel de
     * base de datos (ver migraciones respectivas), no requieren detach() manual aquí.
     */
    public function destroy(Libro $libro)
    {
        if ($libro->ejemplares()->exists()) {
            return back()->with('status', 'No se puede eliminar: el libro tiene ejemplares asociados.');
        }

        $libro->delete();

        return redirect()->route('catalogo.libros.index')->with('status', 'Libro eliminado correctamente.');
    }

    /**
     * Solo los campos propios de la tabla 'libros' — 'autores' y 'categorias' se sincronizan aparte
     * como relaciones M:N, nunca se pasan a create()/update() de Libro directamente.
     */
    private function camposPropios(array $datos): array
    {
        return collect($datos)->except(['autores', 'categorias'])->toArray();
    }

    private function datosDeApoyoParaFormulario(): array
    {
        return [
            'autoresDisponibles' => Autor::orderBy('nombre')->get(),
            'editoriales' => Editorial::orderBy('nombre')->get(),
            // D-06/CL-02: se listan de primer nivel con sus subcategorías, máximo 2 niveles.
            'categoriasDisponibles' => Categoria::whereNull('categoria_padre_id')
                ->with('subcategorias')
                ->orderBy('nombre')
                ->get(),
        ];
    }
}
