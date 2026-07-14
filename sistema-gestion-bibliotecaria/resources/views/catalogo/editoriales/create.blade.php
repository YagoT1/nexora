@extends('layouts.app')

@section('titulo', 'Nueva editorial')

@section('contenido')
    <h1 class="text-xl font-semibold mb-6">Nueva editorial</h1>

    <form method="POST" action="{{ route('catalogo.editoriales.store') }}" class="max-w-md space-y-4 bg-white p-6 border border-gray-200 rounded">
        @csrf

        <div>
            <label class="block text-sm mb-1">Nombre</label>
            <input type="text" name="nombre" value="{{ old('nombre') }}" class="w-full border-gray-300 rounded" required>
            @error('nombre') <p class="text-red-600 text-xs mt-1">{{ $message }}</p> @enderror
        </div>

        <button type="submit" class="rounded bg-gray-900 text-white text-sm px-4 py-2">Crear editorial</button>
    </form>
@endsection
