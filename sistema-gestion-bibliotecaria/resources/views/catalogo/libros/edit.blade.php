@extends('layouts.app')

@section('titulo', 'Editar libro')

@section('contenido')
    <h1 class="text-xl font-semibold mb-6">Editar libro</h1>

    <form method="POST" action="{{ route('catalogo.libros.update', $libro) }}" class="max-w-xl space-y-4 bg-white p-6 border border-gray-200 rounded">
        @csrf
        @method('PUT')

        @include('catalogo.libros._form')

        <button type="submit" class="rounded bg-gray-900 text-white text-sm px-4 py-2">Guardar cambios</button>
    </form>
@endsection
