@extends('layouts.app')

@section('titulo', 'Libros')

@section('contenido')
    @include('catalogo._subnav')

    <div class="flex items-center justify-between mb-6">
        <h1 class="text-xl font-semibold">Libros</h1>
        <a href="{{ route('catalogo.libros.create') }}" class="rounded bg-gray-900 text-white text-sm px-4 py-2">
            Nuevo libro
        </a>
    </div>

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
                    <td class="px-4 py-4 text-gray-500" colspan="4">No hay libros cargados.</td>
                </tr>
            @endforelse
        </tbody>
    </table>

    <div class="mt-4">{{ $libros->links() }}</div>
@endsection
