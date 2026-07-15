{{-- Origen: Plan de Implementación v2, Módulo 5 — alta de reserva sobre un Libro (título, no
     ejemplar puntual). Flujo: buscar/seleccionar socio -> confirmar. --}}
@extends('layouts.app')

@section('titulo', 'Nueva reserva')

@section('contenido')
    <h1 class="text-xl font-semibold mb-2">Reservar "{{ $libro->titulo }}"</h1>
    <p class="text-sm text-gray-500 mb-6">
        <a href="{{ route('catalogo.libros.show', $libro) }}" class="text-blue-700">Volver al libro</a>
    </p>

    {{-- Paso 1: socio --}}
    @if (! $socio)
        <div class="bg-white border border-gray-200 rounded p-6 mb-6 max-w-lg">
            <h2 class="text-sm font-semibold text-gray-700 mb-3">1. Buscar socio</h2>
            <form method="GET" action="{{ route('catalogo.libros.reservas.create', $libro) }}" class="flex gap-2 mb-4">
                <input type="text" name="busqueda_socio" value="{{ $busquedaSocio }}" placeholder="Nombre del socio…" class="flex-1 border-gray-300 rounded">
                <button type="submit" class="rounded bg-gray-900 text-white text-sm px-4 py-2">Buscar</button>
            </form>

            @if ($busquedaSocio !== '')
                <table class="w-full text-sm border border-gray-200 rounded">
                    <tbody>
                        @forelse ($sociosEncontrados as $s)
                            <tr class="border-t border-gray-100">
                                <td class="px-4 py-2">{{ $s->nombre_principal }} <span class="text-gray-500">({{ $s->tipoSocio->nombre }})</span></td>
                                <td class="px-4 py-2 text-right">
                                    <a href="{{ route('catalogo.libros.reservas.create', [$libro, 'socio_id' => $s->id]) }}" class="text-blue-700">
                                        Seleccionar
                                    </a>
                                </td>
                            </tr>
                        @empty
                            <tr><td class="px-4 py-4 text-gray-500" colspan="2">Sin resultados.</td></tr>
                        @endforelse
                    </tbody>
                </table>
            @endif
        </div>
    @else
        <div class="bg-white border border-gray-200 rounded p-6 max-w-lg">
            <div class="flex items-center justify-between mb-4">
                <div>
                    <h2 class="text-sm font-semibold text-gray-700">Socio</h2>
                    <p>{{ $socio->nombre_principal }} — {{ $socio->tipoSocio->nombre }}</p>
                </div>
                <a href="{{ route('catalogo.libros.reservas.create', $libro) }}" class="text-sm text-gray-500">Cambiar socio</a>
            </div>

            @if ($yaTieneReservaActiva)
                <div class="rounded border border-amber-300 bg-amber-50 px-4 py-3 text-sm text-amber-800">
                    Este socio ya tiene una reserva activa para este libro.
                </div>
            @else
                <form method="POST" action="{{ route('catalogo.libros.reservas.store', $libro) }}">
                    @csrf
                    <input type="hidden" name="socio_id" value="{{ $socio->id }}">
                    @error('socio_id') <p class="text-red-600 text-sm mb-3">{{ $message }}</p> @enderror
                    <button type="submit" class="rounded bg-gray-900 text-white text-sm px-4 py-2">Confirmar reserva</button>
                </form>
            @endif
        </div>
    @endif
@endsection
