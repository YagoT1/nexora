@extends('layouts.app')

@section('titulo', 'Editar autor')

@section('contenido')
    <h1 class="text-xl font-semibold mb-6">Editar autor</h1>

    <form method="POST" action="{{ route('catalogo.autores.update', $autor) }}" class="max-w-md space-y-4 bg-white p-6 border border-gray-200 rounded">
        @csrf
        @method('PUT')

        <div>
            <label class="block text-sm mb-1">Nombre</label>
            <input type="text" name="nombre" value="{{ old('nombre', $autor->nombre) }}" class="w-full border-gray-300 rounded" required>
            @error('nombre') <p class="text-red-600 text-xs mt-1">{{ $message }}</p> @enderror
        </div>

        <div>
            <label class="block text-sm mb-1">Notas (opcional)</label>
            <textarea name="notas" class="w-full border-gray-300 rounded" rows="3">{{ old('notas', $autor->notas) }}</textarea>
            @error('notas') <p class="text-red-600 text-xs mt-1">{{ $message }}</p> @enderror
        </div>

        <button type="submit" class="rounded bg-gray-900 text-white text-sm px-4 py-2">Guardar cambios</button>
    </form>
@endsection
