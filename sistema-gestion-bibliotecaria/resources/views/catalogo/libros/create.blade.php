@extends('layouts.app')

@section('titulo', 'Nuevo libro')

@section('contenido')
    <h1 class="text-xl font-semibold mb-6">Nuevo libro</h1>

    <form method="POST" action="{{ route('catalogo.libros.store') }}" class="max-w-xl space-y-4 bg-white p-6 border border-gray-200 rounded">
        @csrf

        @include('catalogo.libros._form')

        <button type="submit" class="rounded bg-gray-900 text-white text-sm px-4 py-2">Crear libro</button>
    </form>
@endsection
