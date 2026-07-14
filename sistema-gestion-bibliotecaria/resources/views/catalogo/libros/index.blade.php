@extends('layouts.app')

@section('titulo', 'Libros')

@php
    // Etiquetas en español para los <select> de estado/modalidad. Única fuente de verdad:
    // Ejemplar::ETIQUETAS_ESTADO / ETIQUETAS_MODALIDAD (Paso 6) — evita repetir el mismo array
    // literal acá y en catalogo/libros/show.blade.php.
    $etiquetasEstado = \App\Models\Ejemplar::ETIQUETAS_ESTADO;
    $etiquetasModalidad = \App\Models\Ejemplar::ETIQUETAS_MODALIDAD;
@endphp

@section('contenido')
    @include('catalogo._subnav')

    <div class="flex items-center justify-between mb-6">
        <h1 class="text-xl font-semibold">Libros</h1>
        <a href="{{ route('catalogo.libros.create') }}" class="rounded bg-gray-900 text-white text-sm px-4 py-2">
            Nuevo libro
        </a>
    </div>

    {{--
        Paso 5 del briefing: búsqueda de catálogo. GET simple (no @csrf: no modifica estado) que
        preserva los filtros en la URL — permite compartir/recargar una búsqueda y funciona con
        withQueryString() en la paginación sin duplicar controles.
    --}}
    <form method="GET" action="{{ route('catalogo.libros.index') }}"
          class="bg-white border border-gray-200 rounded p-4 mb-6 grid grid-cols-1 md:grid-cols-5 gap-3 items-end">
        <div>
            <label class="block text-xs text-gray-600 mb-1" for="f-titulo">Título</label>
            <input type="text" name="titulo" id="f-titulo" value="{{ $filtros['titulo'] ?? '' }}"
                   class="w-full border-gray-300 rounded text-sm" placeholder="Buscar por título">
        </div>
        <div>
            <label class="block text-xs text-gray-600 mb-1" for="f-autor">Autor</label>
            <input type="text" name="autor" id="f-autor" value="{{ $filtros['autor'] ?? '' }}"
                   class="w-full border-gray-300 rounded text-sm" placeholder="Buscar por autor">
        </div>
        <div>
            <label class="block text-xs text-gray-600 mb-1" for="f-categoria">Categoría</label>
            <select name="categoria_id" id="f-categoria" class="w-full border-gray-300 rounded text-sm">
                <option value="">Todas</option>
                @foreach ($categoriasDisponibles as $categoria)
                    <option value="{{ $categoria->id }}"
                            @selected(($filtros['categoria_id'] ?? null) == $categoria->id)>
                        {{ $categoria->nombre }}
                    </option>
                    @foreach ($categoria->subcategorias as $subcategoria)
                        <option value="{{ $subcategoria->id }}"
                                @selected(($filtros['categoria_id'] ?? null) == $subcategoria->id)>
                            &nbsp;&nbsp;— {{ $subcategoria->nombre }}
                        </option>
                    @endforeach
                @endforeach
            </select>
        </div>
        <div>
            <label class="block text-xs text-gray-600 mb-1" for="f-estado">Estado</label>
            <select name="estado" id="f-estado" class="w-full border-gray-300 rounded text-sm">
                <option value="">Todos</option>
                @foreach ($etiquetasEstado as $valor => $etiqueta)
                    <option value="{{ $valor }}" @selected(($filtros['estado'] ?? null) === $valor)>
                        {{ $etiqueta }}
                    </option>
                @endforeach
            </select>
        </div>
        <div>
            <label class="block text-xs text-gray-600 mb-1" for="f-modalidad">Modalidad</label>
            <select name="modalidad" id="f-modalidad" class="w-full border-gray-300 rounded text-sm">
                <option value="">Todas</option>
                @foreach ($etiquetasModalidad as $valor => $etiqueta)
                    <option value="{{ $valor }}" @selected(($filtros['modalidad'] ?? null) === $valor)>
                        {{ $etiqueta }}
                    </option>
                @endforeach
            </select>
        </div>
        <div class="md:col-span-5 flex items-center gap-3">
            <button type="submit" class="rounded bg-gray-900 text-white text-sm px-4 py-2">Buscar</button>
            @if (array_filter($filtros))
                <a href="{{ route('catalogo.libros.index') }}" class="text-sm text-gray-600">Limpiar filtros</a>
            @endif
        </div>
    </form>

    <table class="w-full text-sm bg-white border border-gray-200 rounded">
        <thead class="bg-gray-100 text-left">
            <tr>
                <th class="px-4 py-2">Título</th>
                <th class="px-4 py-2">Autor(es)</th>
                <th class="px-4 py-2">Editorial</th>
                <th class="px-4 py-2"></th>
            </tr>
        </thead>
        <tbody>
            @forelse ($libros as $libro)
                <tr class="border-t border-gray-100">
                    <td class="px-4 py-2">{{ $libro->titulo }}</td>
                    <td class="px-4 py-2">
                        {{ $libro->autores->isNotEmpty() ? $libro->autores->pluck('nombre')->join(', ') : '—' }}
                    </td>
                    <td class="px-4 py-2">{{ $libro->editorial?->nombre ?? '—' }}</td>
                    <td class="px-4 py-2 text-right space-x-3">
                        <a href="{{ route('catalogo.libros.show', $libro) }}" class="text-gray-700">Ver</a>
                        <a href="{{ route('catalogo.libros.edit', $libro) }}" class="text-blue-700">Editar</a>
                        <form method="POST" action="{{ route('catalogo.libros.destroy', $libro) }}" class="inline"
                              onsubmit="return confirm('¿Eliminar este libro?');">
                            @csrf
                            @method('DELETE')
                            <button type="submit" class="text-red-700">Eliminar</button>
                        </form>
                    </td>
                </tr>
            @empty
                <tr>
                    <td class="px-4 py-4 text-gray-500" colspan="4">
                        @if (array_filter($filtros))
                            No hay libros que coincidan con los filtros aplicados.
                        @else
                            No hay libros cargados.
                        @endif
                    </td>
                </tr>
            @endforelse
        </tbody>
    </table>

    <div class="mt-4">{{ $libros->links() }}</div>
@endsection
