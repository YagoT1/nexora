@extends('layouts.app')

@section('titulo', 'Editar ejemplar')

@section('contenido')
    <h1 class="text-xl font-semibold mb-1">Editar ejemplar</h1>
    <p class="text-sm text-gray-500 mb-6">Libro: {{ $libro->titulo }}</p>

    <form method="POST" action="{{ route('catalogo.libros.ejemplares.update', [$libro, $ejemplar]) }}" class="max-w-lg space-y-4 bg-white p-6 border border-gray-200 rounded">
        @csrf
        @method('PUT')

        @include('catalogo.ejemplares._form')

        <div class="flex gap-3">
            <button type="submit" class="rounded bg-gray-900 text-white text-sm px-4 py-2">Guardar cambios</button>
            <a href="{{ route('catalogo.libros.show', $libro) }}" class="text-sm text-gray-500 self-center">Volver al libro</a>
        </div>
    </form>

    <form method="POST" action="{{ route('catalogo.libros.ejemplares.destroy', [$libro, $ejemplar]) }}" class="mt-4"
          onsubmit="return confirm('¿Eliminar este ejemplar?');">
        @csrf
        @method('DELETE')
        <button type="submit" class="text-sm text-red-700">Eliminar ejemplar</button>
    </form>
@endsection
