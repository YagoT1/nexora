@extends('layouts.app')

@section('titulo', 'Detalle de libro')

@section('contenido')
    @include('catalogo._subnav')

    <div class="flex items-center justify-between mb-6">
        <h1 class="text-xl font-semibold">{{ $libro->titulo }}</h1>
        <div class="space-x-3 text-sm">
            <a href="{{ route('catalogo.libros.index') }}" class="text-gray-600">← Volver al listado</a>
            <a href="{{ route('catalogo.libros.edit', $libro) }}" class="text-blue-700">Editar libro</a>
        </div>
    </div>

    <div class="bg-white border border-gray-200 rounded p-6 mb-8 grid grid-cols-1 md:grid-cols-2 gap-x-8 gap-y-3 text-sm">
        <div>
            <span class="block text-xs text-gray-500">ISBN</span>
            {{ $libro->isbn ?? '— (no informado)' }}
        </div>
        <div>
            <span class="block text-xs text-gray-500">Año de publicación</span>
            {{ $libro->anio_publicacion ?? '—' }}
        </div>
        <div>
            <span class="block text-xs text-gray-500">Edición</span>
            {{ $libro->edicion ?? '—' }}
        </div>
        <div>
            <span class="block text-xs text-gray-500">Idioma</span>
            {{ $libro->idioma ?? '—' }}
        </div>
        <div>
            <span class="block text-xs text-gray-500">Editorial</span>
            {{ $libro->editorial?->nombre ?? '— (sin editorial)' }}
        </div>
        <div>
            <span class="block text-xs text-gray-500">Autor(es)</span>
            {{ $libro->autores->isNotEmpty() ? $libro->autores->pluck('nombre')->join(', ') : '— (sin autor identificable)' }}
        </div>
        <div class="md:col-span-2">
            <span class="block text-xs text-gray-500">Categorías</span>
            {{ $libro->categorias->isNotEmpty() ? $libro->categorias->pluck('nombre')->join(', ') : '—' }}
        </div>
        @if ($libro->descripcion)
            <div class="md:col-span-2">
                <span class="block text-xs text-gray-500">Descripción</span>
                {{ $libro->descripcion }}
            </div>
        @endif
    </div>

    {{--
        Paso 6 del briefing (CU-4): listado de ejemplares con estado actual. El estado NUNCA se lee
        de una columna (D-09) — Ejemplar::estadoActual() lo calcula por instancia; acá se llama una
        vez por fila, sin duplicar esa lógica en la vista.
    --}}
    <div class="flex items-center justify-between mb-3">
        <h2 class="text-lg font-semibold">Ejemplares</h2>
        <a href="{{ route('catalogo.libros.ejemplares.create', $libro) }}" class="text-sm text-blue-700">
            + Nuevo ejemplar
        </a>
    </div>

    <table class="w-full text-sm bg-white border border-gray-200 rounded">
        <thead class="bg-gray-100 text-left">
            <tr>
                <th class="px-4 py-2">#</th>
                <th class="px-4 py-2">Estado</th>
                <th class="px-4 py-2">Modalidad</th>
                <th class="px-4 py-2">Condición física</th>
                <th class="px-4 py-2">Origen</th>
                <th class="px-4 py-2">Ingreso</th>
                <th class="px-4 py-2"></th>
            </tr>
        </thead>
        <tbody>
            @forelse ($libro->ejemplares as $ejemplar)
                <tr class="border-t border-gray-100">
                    <td class="px-4 py-2">{{ $ejemplar->id }}</td>
                    <td class="px-4 py-2">
                        {{ \App\Models\Ejemplar::ETIQUETAS_ESTADO[$ejemplar->estadoActual()] ?? $ejemplar->estadoActual() }}
                    </td>
                    <td class="px-4 py-2">
                        {{ \App\Models\Ejemplar::ETIQUETAS_MODALIDAD[$ejemplar->modalidad_acceso] ?? $ejemplar->modalidad_acceso }}
                    </td>
                    <td class="px-4 py-2">{{ $ejemplar->condicion_fisica ?? '—' }}</td>
                    <td class="px-4 py-2 capitalize">{{ $ejemplar->origen }}</td>
                    <td class="px-4 py-2">{{ $ejemplar->fecha_ingreso?->format('d/m/Y') ?? '—' }}</td>
                    <td class="px-4 py-2 text-right space-x-3">
                        {{-- Origen: Módulo 4 — Préstamos, Paso 4. Punto de entrada al registro de
                             préstamo desde el ejemplar concreto; solo se ofrece si puede salir de
                             la biblioteca (RN-08/RN-09) y no tiene ya un movimiento activo. --}}
                        @if ($ejemplar->estadoActual() === \App\Models\Ejemplar::ESTADO_DISPONIBLE && $ejemplar->puedeSalirDeLaBiblioteca())
                            <a href="{{ route('prestamos.create', ['ejemplar_id' => $ejemplar->id]) }}" class="text-green-700">Prestar</a>
                        @endif
                        <a href="{{ route('catalogo.libros.ejemplares.edit', [$libro, $ejemplar]) }}" class="text-blue-700">Editar</a>
                    </td>
                </tr>
            @empty
                <tr>
                    <td class="px-4 py-4 text-gray-500" colspan="7">Este libro todavía no tiene ejemplares cargados.</td>
                </tr>
            @endforelse
        </tbody>
    </table>
@endsection
