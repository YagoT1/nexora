<?php

// Origen: Plan de Implementación v2, Módulo 2 — Catálogo, "Búsqueda de catálogo: por título
// (búsqueda parcial), autor, categoría, estado y modalidad" (Paso 5). Request de solo lectura
// (GET) sobre catalogo.libros.index — valida los filtros antes de construir la consulta en
// LibroController::index(), en vez de leer $request->query() sin validar.

namespace App\Http\Requests\Catalogo;

use App\Models\Ejemplar;
use Illuminate\Foundation\Http\FormRequest;

class LibroSearchRequest extends FormRequest
{
    public function authorize(): bool
    {
        // La autorización de acceso a esta sección ya la resuelve el middleware
        // 'role:administrador,personal' de la ruta; este Request solo valida el formato del filtro.
        return true;
    }

    public function rules(): array
    {
        return [
            'titulo' => ['nullable', 'string', 'max:255'],
            'autor' => ['nullable', 'string', 'max:255'],
            'categoria_id' => ['nullable', 'integer', 'exists:categorias,id'],
            'estado' => ['nullable', 'in:' . implode(',', Ejemplar::ESTADOS_OPERATIVOS)],
            'modalidad' => ['nullable', 'in:' . implode(',', Ejemplar::MODALIDADES_ACCESO)],
        ];
    }
}
