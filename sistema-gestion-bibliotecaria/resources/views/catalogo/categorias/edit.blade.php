@extends('layouts.app')

@section('titulo', 'Editar categoría')

@section('contenido')
    <h1 class="text-xl font-semibold mb-6">Editar categoría</h1>

    <form method="POST" action="{{ route('catalogo.categorias.update', $categoria) }}" class="max-w-md space-y-4 bg-white p-6 border border-gray-200 rounded">
        @csrf
        @method('PUT')

        <div>
            <label class="block text-sm mb-1">Nombre</label>
            <input type="text" name="nombre" value="{{ old('nombre', $categoria->nombre) }}" class="w-full border-gray-300 rounded" required>
            @error('nombre') <p class="text-red-600 text-xs mt-1">{{ $message }}</p> @enderror
        </div>

        <div>
            <label class="block text-sm mb-1">Categoría padre (opcional)</label>
            @if ($categoriasPadre->isEmpty())
                <p class="text-xs text-gray-500">
                    Esta categoría ya tiene subcategorías propias, por lo que no puede tener padre
                    (profundidad máxima: 2 niveles).
                </p>
                <input type="hidden" name="categoria_padre_id" value="">
            @else
                <select name="categoria_padre_id" class="w-full border-gray-300 rounded">
                    <option value="">— Categoría de primer nivel —</option>
                    @foreach ($categoriasPadre as $padre)
                        <option value="{{ $padre->id }}" {{ (string) old('categoria_padre_id', $categoria->categoria_padre_id) === (string) $padre->id ? 'selected' : '' }}>
                            {{ $padre->nombre }}
                        </option>
                    @endforeach
                </select>
                <p class="text-xs text-gray-500 mt-1">Máximo 2 niveles: solo se listan aquí categorías de primer nivel.</p>
            @endif
            @error('categoria_padre_id') <p class="text-red-600 text-xs mt-1">{{ $message }}</p> @enderror
        </div>

        <button type="submit" class="rounded bg-gray-900 text-white text-sm px-4 py-2">Guardar cambios</button>
    </form>
@endsection
