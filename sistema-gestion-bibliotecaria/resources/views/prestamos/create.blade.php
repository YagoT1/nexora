{{-- Origen: Plan de Implementación v2, Módulo 4 — registro de préstamo domiciliario.
     Flujo: buscar/seleccionar socio -> verificar estado -> buscar/seleccionar ejemplar -> confirmar. --}}
@extends('layouts.app')

@section('titulo', 'Nuevo préstamo')

@section('contenido')
    <h1 class="text-xl font-semibold mb-6">Registrar préstamo</h1>

    {{-- Paso 1: socio --}}
    @if (! $socio)
        <div class="bg-white border border-gray-200 rounded p-6 mb-6">
            <h2 class="text-sm font-semibold text-gray-700 mb-3">1. Buscar socio</h2>
            <form method="GET" action="{{ route('prestamos.create') }}" class="flex gap-2 mb-4">
                @if (request()->filled('ejemplar_id'))
                    <input type="hidden" name="ejemplar_id" value="{{ request('ejemplar_id') }}">
                @endif
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
                                    <a href="{{ route('prestamos.create', array_filter(['socio_id' => $s->id, 'ejemplar_id' => request('ejemplar_id')])) }}" class="text-blue-700">
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
        <div class="bg-white border border-gray-200 rounded p-6 mb-6">
            <div class="flex items-center justify-between">
                <div>
                    <h2 class="text-sm font-semibold text-gray-700">Socio</h2>
                    <p>{{ $socio->nombre_principal }} — {{ $socio->tipoSocio->nombre }}
                        ({{ $cantidadPrestamosActivos }}/{{ $socio->tipoSocio->limite_prestamos_simultaneos }} préstamos activos)</p>
                </div>
                <a href="{{ route('prestamos.create', array_filter(['ejemplar_id' => $ejemplar?->id])) }}" class="text-sm text-gray-500">Cambiar socio</a>
            </div>

            @if ($restriccionVigente)
                <div class="mt-3 rounded border border-red-300 bg-red-50 px-4 py-3 text-sm text-red-800">
                    <strong>Restricción vigente</strong> hasta {{ $restriccionVigente->fecha_fin->format('d/m/Y') }}
                    {{ $restriccionVigente->observaciones ? ' — '.$restriccionVigente->observaciones : '' }}.
                    Solo puede recibir el préstamo si existe una Excepción Autorizada vigente de tipo "Exención" (Módulo 6).
                </div>
            @endif

            @if ($cantidadPrestamosActivos >= $socio->tipoSocio->limite_prestamos_simultaneos)
                <div class="mt-3 rounded border border-amber-300 bg-amber-50 px-4 py-3 text-sm text-amber-800">
                    El socio ya alcanzó el límite de préstamos simultáneos de su tipo. Para continuar, vas a tener que
                    completar un motivo de excepción al confirmar.
                </div>
            @endif
        </div>
    @endif

    {{-- Paso 2: ejemplar --}}
    @if ($socio && ! $ejemplar)
        <div class="bg-white border border-gray-200 rounded p-6 mb-6">
            <h2 class="text-sm font-semibold text-gray-700 mb-3">2. Buscar ejemplar disponible</h2>
            <form method="GET" action="{{ route('prestamos.create') }}" class="flex gap-2 mb-4">
                <input type="hidden" name="socio_id" value="{{ $socio->id }}">
                <input type="text" name="busqueda_libro" value="{{ $busquedaLibro }}" placeholder="Título del libro…" class="flex-1 border-gray-300 rounded">
                <button type="submit" class="rounded bg-gray-900 text-white text-sm px-4 py-2">Buscar</button>
            </form>

            @if ($busquedaLibro !== '')
                <table class="w-full text-sm border border-gray-200 rounded">
                    <tbody>
                        @forelse ($ejemplaresEncontrados as $e)
                            <tr class="border-t border-gray-100">
                                <td class="px-4 py-2">{{ $e->libro->titulo }} <span class="text-gray-500">(ejemplar #{{ $e->id }})</span></td>
                                <td class="px-4 py-2 text-right">
                                    <a href="{{ route('prestamos.create', ['socio_id' => $socio->id, 'ejemplar_id' => $e->id]) }}" class="text-blue-700">
                                        Seleccionar
                                    </a>
                                </td>
                            </tr>
                        @empty
                            <tr><td class="px-4 py-4 text-gray-500" colspan="2">Sin ejemplares disponibles para ese título.</td></tr>
                        @endforelse
                    </tbody>
                </table>
            @endif
        </div>
    @elseif ($ejemplar)
        <div class="bg-white border border-gray-200 rounded p-6 mb-6">
            <div class="flex items-center justify-between">
                <div>
                    <h2 class="text-sm font-semibold text-gray-700">Ejemplar</h2>
                    <p>{{ $ejemplar->libro->titulo }} (#{{ $ejemplar->id }})</p>
                </div>
                @if ($socio)
                    <a href="{{ route('prestamos.create', ['socio_id' => $socio->id]) }}" class="text-sm text-gray-500">Cambiar ejemplar</a>
                @endif
            </div>
        </div>
    @endif

    {{-- Paso 3: confirmar --}}
    @if ($socio && $ejemplar)
        <div class="bg-white border border-gray-200 rounded p-6 max-w-md">
            <h2 class="text-sm font-semibold text-gray-700 mb-3">3. Confirmar préstamo</h2>
            <form method="POST" action="{{ route('prestamos.store') }}" class="space-y-4">
                @csrf
                <input type="hidden" name="socio_id" value="{{ $socio->id }}">
                <input type="hidden" name="ejemplar_id" value="{{ $ejemplar->id }}">

                <div>
                    <label class="block text-sm mb-1">Fecha de préstamo</label>
                    <input type="date" name="fecha_prestamo" value="{{ old('fecha_prestamo', now()->toDateString()) }}" class="w-full border-gray-300 rounded" required>
                    @error('fecha_prestamo') <p class="text-red-600 text-xs mt-1">{{ $message }}</p> @enderror
                </div>

                <div>
                    <label class="block text-sm mb-1">Motivo de excepción (solo si se supera el límite o hay restricción con excepción vigente)</label>
                    <textarea name="motivo_excepcion_limite" class="w-full border-gray-300 rounded" rows="2">{{ old('motivo_excepcion_limite') }}</textarea>
                    @error('motivo_excepcion_limite') <p class="text-red-600 text-xs mt-1">{{ $message }}</p> @enderror
                </div>

                @error('ejemplar_id') <p class="text-red-600 text-sm">{{ $message }}</p> @enderror
                @error('socio_id') <p class="text-red-600 text-sm">{{ $message }}</p> @enderror

                <button type="submit" class="rounded bg-gray-900 text-white text-sm px-4 py-2">Registrar préstamo</button>
            </form>
        </div>
    @endif
@endsection
