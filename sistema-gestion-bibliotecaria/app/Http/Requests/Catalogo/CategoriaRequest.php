<?php

// Origen: Plan de Implementación v2, Módulo 2 — Catálogo, criterio de aceptación "El sistema no
// permite crear una subcategoría cuyo padre ya es subcategoría (profundidad máxima 2)" y D-06/CL-02
// (Modelo de Dominio v2). FormRequest reutilizable entre alta y edición para mitigar el riesgo R-3
// documentado en Fase 6 - Development/BRIEFING-MODULO-2-CATALOGO.md: la validación de profundidad
// solo existía como helper (Categoria::puedeSerPadre()) sin ningún punto de entrada que la aplicara.

namespace App\Http\Requests\Catalogo;

use App\Models\Categoria;
use Illuminate\Foundation\Http\FormRequest;

class CategoriaRequest extends FormRequest
{
    public function rules(): array
    {
        return [
            'nombre' => ['required', 'string', 'max:255'],
            'categoria_padre_id' => ['nullable', 'integer', 'exists:categorias,id'],
        ];
    }

    /**
     * Validación de profundidad máxima (D-06/CL-02), en ambas direcciones:
     *
     * 1. La categoría padre elegida debe ser de primer nivel (puedeSerPadre()). Es el criterio de
     *    aceptación explícito del módulo, para el caso de alta.
     * 2. Una categoría que ya tiene subcategorías propias no puede pasar a tener padre: eso
     *    convertiría a sus hijas en un tercer nivel implícito. No está redactado literalmente en
     *    el criterio de aceptación (que solo cubre el alta), pero es la misma invariante D-06
     *    aplicada en el sentido inverso durante una edición — dejarla sin cubrir permitiría violar
     *    la profundidad máxima únicamente por editar en lugar de crear.
     * 3. Una categoría no puede ser su propia padre.
     */
    public function withValidator($validator): void
    {
        $validator->after(function ($validator) {
            $padreId = $this->input('categoria_padre_id');

            if (! $padreId) {
                return;
            }

            // El nombre del parámetro de ruta se fija explícitamente a 'categoria' en routes/web.php
            // (Route::resource(..., ['parameters' => ['categorias' => 'categoria']])) para no
            // depender del singularizador automático de Laravel sobre un sustantivo en español.
            $categoriaActual = $this->route('categoria');

            if ($categoriaActual instanceof Categoria && (int) $padreId === $categoriaActual->id) {
                $validator->errors()->add('categoria_padre_id', 'Una categoría no puede ser su propia categoría padre.');

                return;
            }

            $padre = Categoria::find($padreId);

            if (! $padre || ! $padre->puedeSerPadre()) {
                $validator->errors()->add(
                    'categoria_padre_id',
                    'La categoría padre seleccionada ya es una subcategoría (profundidad máxima: 2 niveles).'
                );

                return;
            }

            if ($categoriaActual instanceof Categoria && $categoriaActual->subcategorias()->exists()) {
                $validator->errors()->add(
                    'categoria_padre_id',
                    'Esta categoría ya tiene subcategorías propias; no puede convertirse en subcategoría de otra (profundidad máxima: 2 niveles).'
                );
            }
        });
    }
}
