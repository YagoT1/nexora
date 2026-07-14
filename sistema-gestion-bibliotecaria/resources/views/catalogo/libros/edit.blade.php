@extends('layouts.app')

@section('titulo', 'Editar libro')

@section('contenido')
    <div class="flex items-center justify-between mb-6 max-w-xl">
        <h1 class="text-xl font-semibold">Editar libro</h1>
        <a href="{{ route('catalogo.libros.show', $libro) }}" class="text-sm text-blue-700">
            Ver detalle y ejemplares →
        </a>
    </div>

    <form method="POST" action="{{ route('catalogo.libros.update', $libro) }}" class="max-w-xl space-y-4 bg-white p-6 border border-gray-200 rounded">
        @csrf
        @method('PUT')

        @include('catalogo.libros._form')

        <button type="submit" class="rounded bg-gray-900 text-white text-sm px-4 py-2">Guardar cambios</button>
    </form>
@endsection
