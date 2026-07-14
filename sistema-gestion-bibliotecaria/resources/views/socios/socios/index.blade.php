{{-- Criterio de aceptación Módulo 3: búsqueda tolerante a acentos y mayúsculas/minúsculas (R-1). --}}
@extends('layouts.app')

@section('titulo', 'Socios')

@section('contenido')
    @include('socios._subnav')

    <div class="flex items-center justify-between mb-6">
        <h1 class="text-xl font-semibold">Socios</h1>
        <a href="{{ route('socios.socios.create') }}" class="rounded bg-gray-900 text-white text-sm px-4 py-2">
            Nuevo socio
        </a>
    </div>

    <form method="GET" action="{{ route('socios.socios.index') }}" class="mb-6 flex gap-2 max-w-md">
        <input type="text" name="busqueda" value="{{ $busqueda }}" placeholder="Buscar por nombre (principal o alternativo)…"
               class="w-full border-gray-300 rounded text-sm">
        <button type="submit" class="rounded bg-gray-900 text-white text-sm px-4 py-2 whitespace-nowrap">Buscar</button>
        @if ($busqueda !== '')
            <a href="{{ route('socios.socios.index') }}" class="text-sm text-gray-500 self-center whitespace-nowrap">Limpiar</a>
        @endif
    </form>

    <table class="w-full text-sm bg-white border border-gray-200 rounded">
        <thead class="bg-gray-100 text-left">
            <tr>
                <th class="px-4 py-2">Nombre principal</th>
                <th class="px-4 py-2">DNI</th>
                <th class="px-4 py-2">Tipo de socio</th>
                <th class="px-4 py-2">Estado</th>
                <th class="px-4 py-2"></th>
            </tr>
        </thead>
        <tbody>
            @forelse ($socios as $socio)
                <tr class="border-t border-gray-100">
                    <td class="px-4 py-2">{{ $socio->nombre_principal }}</td>
                    <td class="px-4 py-2">{{ $socio->dni ?? '—' }}</td>
                    <td class="px-4 py-2">{{ $socio->tipoSocio->nombre }}</td>
                    <td class="px-4 py-2">{{ $socio->estado === 'activo' ? 'Activo' : 'Inactivo' }}</td>
                    <td class="px-4 py-2 text-right space-x-3">
                        <a href="{{ route('socios.socios.show', $socio) }}" class="text-blue-700">Ver</a>
                        <a href="{{ route('socios.socios.edit', $socio) }}" class="text-blue-700">Editar</a>
                    </td>
                </tr>
            @empty
                <tr>
                    <td class="px-4 py-4 text-gray-500" colspan="5">
                        @if ($busqueda !== '')
                            Ningún socio coincide con "{{ $busqueda }}".
                        @else
                            No hay socios cargados.
                        @endif
                    </td>
                </tr>
            @endforelse
        </tbody>
    </table>

    <div class="mt-4">{{ $socios->links() }}</div>
@endsection
