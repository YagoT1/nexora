@extends('layouts.app')

@section('titulo', 'Nueva categoría')

@section('contenido')
    <h1 class="text-xl font-semibold mb-6">Nueva categoría</h1>

    <form method="POST" action="{{ route('catalogo.categorias.store') }}" class="max-w-md space-y-4 bg-white p-6 border border-gray-200 rounded">
        @csrf

        <div>
            <label class="block text-sm mb-1">Nombre</label>
            <input type="text" name="nombre" value="{{ old('nombre') }}" class="w-full border-gray-300 rounded" required>
            @error('nombre') <p class="text-red-600 text-xs mt-1">{{ $message }}</p> @enderror
        </div>

        <div>
            <label class="block text-sm mb-1">Categoría padre (opcional)</label>
            <select name="categoria_padre_id" class="w-full border-gray-300 rounded">
                <option value="">— Categoría de primer nivel —</option>
                @foreach ($categoriasPadre as $padre)
                    <option value="{{ $padre->id }}" {{ (string) old('categoria_padre_id') === (string) $padre->id ? 'selected' : '' }}>
                        {{ $padre->nombre }}
                    </option>
                @endforeach
            </select>
            <p class="text-xs text-gray-500 mt-1">Máximo 2 niveles: solo se listan aquí categorías de primer nivel.</p>
            @error('categoria_padre_id') <p class="text-red-600 text-xs mt-1">{{ $message }}</p> @enderror
        </div>

        <button type="submit" class="rounded bg-gray-900 text-white text-sm px-4 py-2">Crear categoría</button>
    </form>
@endsection
