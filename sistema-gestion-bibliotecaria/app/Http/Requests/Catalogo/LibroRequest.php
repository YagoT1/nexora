<?php

// Origen: Plan de Implementación v2, Módulo 2 — Catálogo, "CRUD de Libro". Modelo de Dominio v2,
// 1.1 "Libro": título obligatorio; ISBN, año, edición, idioma, descripción y editorial opcionales;
// autores y categorías son relaciones M:N. Importante: el propio Modelo de Dominio aclara que "un
// libro puede no tener autor identificable (recopilaciones, obras anónimas)" — por eso 'autores'
// no se valida como obligatorio ni con mínimo 1, pese a que el criterio de aceptación del módulo
// solo ejemplifica el caso con autores. No se inventa una restricción que el dominio no impone.

namespace App\Http\Requests\Catalogo;

use Illuminate\Foundation\Http\FormRequest;

class LibroRequest extends FormRequest
{
    public function rules(): array
    {
        $anioMaximo = (int) date('Y') + 1;

        return [
            'titulo' => ['required', 'string', 'max:255'],
            // D-07: ISBN no es identificador único; no se valida 'unique'.
            'isbn' => ['nullable', 'string', 'max:50'],
            'anio_publicacion' => ['nullable', 'integer', 'min:1000', 'max:' . $anioMaximo],
            'edicion' => ['nullable', 'string', 'max:100'],
            'idioma' => ['nullable', 'string', 'max:100'],
            'descripcion' => ['nullable', 'string'],
            'editorial_id' => ['nullable', 'integer', 'exists:editoriales,id'],
            'autores' => ['nullable', 'array'],
            'autores.*' => ['integer', 'exists:autores,id'],
            'categorias' => ['nullable', 'array'],
            'categorias.*' => ['integer', 'exists:categorias,id'],
        ];
    }
}
