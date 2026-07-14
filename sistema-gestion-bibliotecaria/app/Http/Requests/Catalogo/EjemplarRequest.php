<?php

// Origen: Plan de Implementación v2, Módulo 2 — Catálogo, "CRUD de Ejemplar: vinculado a Libro, con
// estado operativo manual (En reparación / Extraviado) y modalidad de acceso (Libre circulación /
// Solo sala / Restringido a autorización), condición física, fecha de ingreso, origen". Valores
// tomados de App\Models\Ejemplar::ESTADOS_MANUALES/MODALIDADES_ACCESO/ORIGENES (coinciden con la
// migración 2024_01_01_000090_create_ejemplares_table.php).
//
// 'libro_id' NO se valida aquí: el Ejemplar siempre se crea/edita en el contexto de un Libro dado
// por la ruta anidada (libros.ejemplares), y el controlador lo asigna directamente — no viene del
// formulario, para no permitir que un usuario reasigne el ejemplar a otro libro por esta vía.

namespace App\Http\Requests\Catalogo;

use App\Models\Ejemplar;
use Illuminate\Foundation\Http\FormRequest;

class EjemplarRequest extends FormRequest
{
    public function rules(): array
    {
        return [
            'estado_manual' => ['nullable', 'in:' . implode(',', Ejemplar::ESTADOS_MANUALES)],
            'modalidad_acceso' => ['required', 'in:' . implode(',', Ejemplar::MODALIDADES_ACCESO)],
            'condicion_fisica' => ['nullable', 'string'],
            'fecha_ingreso' => ['required', 'date'],
            'origen' => ['required', 'in:' . implode(',', Ejemplar::ORIGENES)],
        ];
    }
}
