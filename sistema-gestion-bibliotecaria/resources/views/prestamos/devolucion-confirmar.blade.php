{{-- Origen: Plan de Implementación v2, Módulo 4 — confirmación de devolución. Condición física
     opcional; sin campo de socio (RN-12). --}}
@extends('layouts.app')

@section('titulo', 'Confirmar devolución')

@section('contenido')
    <h1 class="text-xl font-semibold mb-6">Confirmar devolución</h1>

    <div class="bg-white border border-gray-200 rounded p-6 max-w-md">
        <p class="text-sm text-gray-700 mb-1"><strong>{{ $prestamo->ejemplar->libro->titulo }}</strong> (#{{ $prestamo->ejemplar->id }})</p>
        <p class="text-sm text-gray-500 mb-4">
            Prestado a {{ $prestamo->socio->nombre_principal }} — vencía el {{ $prestamo->fecha_vencimiento->format('d/m/Y') }}
        </p>

        <form method="POST" action="{{ route('prestamos.devolucion.store', $prestamo) }}" class="space-y-4">
            @csrf
            <div>
                <label class="block text-sm mb-1">Fecha de devolución</label>
                <input type="date" name="fecha_devolucion_efectiva" value="{{ old('fecha_devolucion_efectiva', now()->toDateString()) }}" class="w-full border-gray-300 rounded" required>
                @error('fecha_devolucion_efectiva') <p class="text-red-600 text-xs mt-1">{{ $message }}</p> @enderror
            </div>

            <div>
                <label class="block text-sm mb-1">Condición física (opcional)</label>
                <textarea name="condicion_fisica" class="w-full border-gray-300 rounded" rows="2">{{ old('condicion_fisica') }}</textarea>
                @error('condicion_fisica') <p class="text-red-600 text-xs mt-1">{{ $message }}</p> @enderror
            </div>

            <button type="submit" class="rounded bg-gray-900 text-white text-sm px-4 py-2">Confirmar devolución</button>
        </form>
    </div>
@endsection
