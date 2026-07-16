{{-- Origen: Plan de Implementación v2, Módulo 6 — Excepciones y restricciones, CU-3. Ver
     Fase 6 - Development/BRIEFING-MODULO-6-EXCEPCIONES-RESTRICCIONES.md, Paso 4. Alta manual
     (Personal o Administrador) y listado de restricciones activas/históricas de un socio. --}}
@extends('layouts.app')

@section('titulo', 'Restricciones de '.$socio->nombre_principal)

@section('contenido')
    <div class="flex items-center justify-between mb-6">
        <div>
            <h1 class="text-xl font-semibold">Restricciones — {{ $socio->nombre_principal }}</h1>
            <p class="text-sm text-gray-500">{{ $socio->tipoSocio->nombre }}</p>
        </div>
        <a href="{{ route('socios.socios.show', $socio) }}" class="text-sm text-gray-500">Volver al socio</a>
    </div>

    @if (session('status'))
        <div class="mb-6 rounded border border-green-300 bg-green-50 px-4 py-3 text-sm text-green-800">
            {{ session('status') }}
        </div>
    @endif

    <div class="bg-white border border-gray-200 rounded p-6 mb-8 max-w-md">
        <h2 class="text-sm font-semibold text-gray-700 mb-3">Nueva restricción manual</h2>
        <form method="POST" action="{{ route('restricciones.store', $socio) }}" class="space-y-4">
            @csrf

            <div>
                <label class="block text-sm mb-1">Motivo</label>
                <textarea name="observaciones" class="w-full border-gray-300 rounded" rows="3" required>{{ old('observaciones') }}</textarea>
                @error('observaciones') <p class="text-red-600 text-xs mt-1">{{ $message }}</p> @enderror
            </div>

            <div>
                <label class="block text-sm mb-1">Fecha de fin</label>
                <input type="date" name="fecha_fin" value="{{ old('fecha_fin') }}" class="w-full border-gray-300 rounded" required>
                @error('fecha_fin') <p class="text-red-600 text-xs mt-1">{{ $message }}</p> @enderror
            </div>

            <button type="submit" class="rounded bg-gray-900 text-white text-sm px-4 py-2">Crear restricción</button>
        </form>
    </div>

    <h2 class="text-sm font-semibold text-gray-700 mb-3">Historial de restricciones</h2>
    <table class="w-full text-sm bg-white border border-gray-200 rounded">
        <thead class="bg-gray-100 text-left">
            <tr>
                <th class="px-4 py-2">Tipo</th>
                <th class="px-4 py-2">Vigencia</th>
                <th class="px-4 py-2">Motivo</th>
                <th class="px-4 py-2">Generada por</th>
                <th class="px-4 py-2">Estado</th>
            </tr>
        </thead>
        <tbody>
            @forelse ($restricciones as $restriccion)
                <tr class="border-t border-gray-100">
                    <td class="px-4 py-2">{{ $restriccion->tipo === \App\Models\RestriccionSocio::TIPO_MANUAL ? 'Manual' : 'Automática' }}</td>
                    <td class="px-4 py-2">{{ $restriccion->fecha_inicio->format('d/m/Y') }} – {{ $restriccion->fecha_fin->format('d/m/Y') }}</td>
                    <td class="px-4 py-2 max-w-xs truncate" title="{{ $restriccion->observaciones }}">{{ $restriccion->observaciones ?? '—' }}</td>
                    <td class="px-4 py-2">{{ $restriccion->generadaPor->name ?? 'Sistema' }}</td>
                    <td class="px-4 py-2">
                        @if ($restriccion->estaActiva())
                            <span class="inline-block rounded bg-red-100 text-red-800 px-2 py-0.5 text-xs">Activa</span>
                        @else
                            <span class="inline-block rounded bg-gray-100 text-gray-700 px-2 py-0.5 text-xs">Finalizada</span>
                        @endif
                    </td>
                </tr>
            @empty
                <tr>
                    <td class="px-4 py-4 text-gray-500" colspan="5">Este socio no tiene restricciones registradas.</td>
                </tr>
            @endforelse
        </tbody>
    </table>
@endsection
