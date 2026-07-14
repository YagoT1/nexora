{{-- Criterio de aceptación Módulo 2: CRUD de Autor (sin dependencias internas, ver Briefing Módulo 2, paso 1). --}}
@extends('layouts.app')

@section('titulo', 'Autores')

@section('contenido')
    <div class="flex items-center justify-between mb-6">
        <h1 class="text-xl font-semibold">Autores</h1>
        <a href="{{ route('catalogo.autores.create') }}" class="rounded bg-gray-900 text-white text-sm px-4 py-2">
            Nuevo autor
        </a>
    </div>

    <table class="w-full text-sm bg-white border border-gray-200 rounded">
        <thead class="bg-gray-100 text-left">
            <tr>
                <th class="px-4 py-2">Nombre</th>
                <th class="px-4 py-2">Libros</th>
                <th class="px-4 py-2"></th>
            </tr>
        </thead>
        <tbody>
            @forelse ($autores as $autor)
                <tr class="border-t border-gray-100">
                    <td class="px-4 py-2">{{ $autor->nombre }}</td>
                    <td class="px-4 py-2">{{ $autor->libros_count }}</td>
                    <td class="px-4 py-2 text-right space-x-3">
                        <a href="{{ route('catalogo.autores.edit', $autor) }}" class="text-blue-700">Editar</a>
                        <form method="POST" action="{{ route('catalogo.autores.destroy', $autor) }}" class="inline"
                              onsubmit="return confirm('¿Eliminar este autor?');">
                            @csrf
                            @method('DELETE')
                            <button type="submit" class="text-red-700">Eliminar</button>
                        </form>
                    </td>
                </tr>
            @empty
                <tr>
                    <td class="px-4 py-4 text-gray-500" colspan="3">No hay autores cargados.</td>
                </tr>
            @endforelse
        </tbody>
    </table>

    <div class="mt-4">{{ $autores->links() }}</div>
@endsection
