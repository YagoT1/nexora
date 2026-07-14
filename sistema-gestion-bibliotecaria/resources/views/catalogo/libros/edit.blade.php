@extends('layouts.app')

@section('titulo', 'Editar libro')

@section('contenido')
    <h1 class="text-xl font-semibold mb-6">Editar libro</h1>

    <form method="POST" action="{{ route('catalogo.libros.update', $libro) }}" class="max-w-xl space-y-4 bg-white p-6 border border-gray-200 rounded">
        @csrf
        @method('PUT')

        @include('catalogo.libros._form')

        <button type="submit" class="rounded bg-gray-900 text-white text-sm px-4 py-2">Guardar cambios</button>
    </form>

    {{-- Gestión de ejemplares: provisoria en esta pantalla hasta el Paso 6 (vista de detalle de
         Libro), que la reemplazará por una vista propia con búsqueda y estado más completo. --}}
    <div class="max-w-xl mt-8">
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
                    <th class="px-4 py-2"></th>
                </tr>
            </thead>
            <tbody>
                @forelse ($libro->ejemplares as $ejemplar)
                    <tr class="border-t border-gray-100">
                        <td class="px-4 py-2">{{ $ejemplar->id }}</td>
                        <td class="px-4 py-2 capitalize">{{ str_replace('_', ' ', $ejemplar->estadoActual()) }}</td>
                        <td class="px-4 py-2 capitalize">{{ str_replace('_', ' ', $ejemplar->modalidad_acceso) }}</td>
                        <td class="px-4 py-2 text-right">
                            <a href="{{ route('catalogo.libros.ejemplares.edit', [$libro, $ejemplar]) }}" class="text-blue-700">Editar</a>
                        </td>
                    </tr>
                @empty
                    <tr>
                        <td class="px-4 py-4 text-gray-500" colspan="4">Este libro todavía no tiene ejemplares cargados.</td>
                    </tr>
                @endforelse
            </tbody>
        </table>
    </div>
@endsection
