@extends('layouts.app')

@section('titulo', 'Nuevo tipo de socio')

@section('contenido')
    <h1 class="text-xl font-semibold mb-6">Nuevo tipo de socio</h1>

    <form method="POST" action="{{ route('socios.tipos-socio.store') }}" class="max-w-md space-y-4 bg-white p-6 border border-gray-200 rounded">
        @csrf

        <div>
            <label class="block text-sm mb-1">Nombre</label>
            <input type="text" name="nombre" value="{{ old('nombre') }}" class="w-full border-gray-300 rounded" required>
            @error('nombre') <p class="text-red-600 text-xs mt-1">{{ $message }}</p> @enderror
        </div>

        <div>
            <label class="block text-sm mb-1">Límite de préstamos simultáneos</label>
            <input type="number" name="limite_prestamos_simultaneos" min="0" value="{{ old('limite_prestamos_simultaneos') }}" class="w-full border-gray-300 rounded" required>
            @error('limite_prestamos_simultaneos') <p class="text-red-600 text-xs mt-1">{{ $message }}</p> @enderror
        </div>

        <div class="flex items-center gap-2">
            <input type="checkbox" name="sujeto_a_restriccion_automatica" value="1" id="sujeto_a_restriccion_automatica"
                   {{ old('sujeto_a_restriccion_automatica', true) ? 'checked' : '' }} class="rounded border-gray-300">
            <label for="sujeto_a_restriccion_automatica" class="text-sm">Sujeto a restricción automática por atraso (RN-07: desmarcar para socios tipo Honorario)</label>
        </div>

        <button type="submit" class="rounded bg-gray-900 text-white text-sm px-4 py-2">Crear tipo de socio</button>
    </form>
@endsection
