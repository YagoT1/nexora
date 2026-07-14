<?php

// Origen: Plan de Implementación v2, Módulo 2 — Catálogo, "CRUD de Categoría (con padre opcional,
// máximo 2 niveles de profundidad, validado)". Ruta protegida por middleware
// role:administrador,personal (Modelo de Dominio v2, 6.1).

namespace App\Http\Controllers\Catalogo;

use App\Http\Controllers\Controller;
use App\Http\Requests\Catalogo\CategoriaRequest;
use App\Models\Categoria;

class CategoriaController extends Controller
{
    public function index()
    {
        // Se cargan solo las categorías de primer nivel con sus subcategorías (máximo 2 niveles,
        // D-06/CL-02): la vista no necesita recorrer más profundidad que esa.
        $categorias = Categoria::whereNull('categoria_padre_id')
            ->with('subcategorias')
            ->withCount('libros')
            ->orderBy('nombre')
            ->get();

        return view('catalogo.categorias.index', compact('categorias'));
    }

    public function create()
    {
        return view('catalogo.categorias.create', [
            'categoriasPadre' => $this->categoriasElegiblesComoPadre(),
        ]);
    }

    public function store(CategoriaRequest $request)
    {
        Categoria::create($request->validated());

        return redirect()->route('catalogo.categorias.index')->with('status', 'Categoría creada correctamente.');
    }

    public function edit(Categoria $categoria)
    {
        return view('catalogo.categorias.edit', [
            'categoria' => $categoria,
            'categoriasPadre' => $this->categoriasElegiblesComoPadre($categoria),
        ]);
    }

    public function update(CategoriaRequest $request, Categoria $categoria)
    {
        $categoria->update($request->validated());

        return redirect()->route('catalogo.categorias.index')->with('status', 'Categoría actualizada correctamente.');
    }

    /**
     * No se permite eliminar una categoría con subcategorías (dejaría a las hijas huérfanas de un
     * padre que ya no está bajo revisión explícita del personal) ni con libros asociados — mismo
     * criterio que AutorController/EditorialController.
     */
    public function destroy(Categoria $categoria)
    {
        if ($categoria->subcategorias()->exists()) {
            return back()->with('status', 'No se puede eliminar: la categoría tiene subcategorías.');
        }

        if ($categoria->libros()->exists()) {
            return back()->with('status', 'No se puede eliminar: la categoría tiene libros asociados.');
        }

        $categoria->delete();

        return redirect()->route('catalogo.categorias.index')->with('status', 'Categoría eliminada correctamente.');
    }

    /**
     * Categorías que pueden elegirse como padre en el formulario: de primer nivel
     * (Categoria::puedeSerPadre()) y, en edición, nunca la propia categoría ni ninguna que ya
     * tenga subcategorías (ver CategoriaRequest::withValidator() para la validación de servidor
     * equivalente — esto solo evita ofrecer en la UI una opción que el servidor rechazaría).
     */
    private function categoriasElegiblesComoPadre(?Categoria $categoriaActual = null)
    {
        // Si la categoría ya tiene subcategorías propias, no puede tener padre (ver
        // CategoriaRequest::withValidator()): no tiene sentido ofrecer opciones en el formulario.
        if ($categoriaActual && $categoriaActual->subcategorias()->exists()) {
            return collect();
        }

        return Categoria::whereNull('categoria_padre_id')
            ->when($categoriaActual, fn ($query) => $query->whereKeyNot($categoriaActual->id))
            ->orderBy('nombre')
            ->get();
    }
}
