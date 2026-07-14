{{-- Criterio de aceptación Módulo 2: CRUD de Categoría con profundidad máxima 2 (D-06/CL-02). --}}
@extends('layouts.app')

@section('titulo', 'Categorías')

@section('contenido')
    <div class="flex items-center justify-between mb-6">
        <h1 class="text-xl font-semibold">Categorías</h1>
        <a href="{{ route('catalogo.categorias.create') }}" class="rounded bg-gray-900 text-white text-sm px-4 py-2">
            Nueva categoría
        </a>
    </div>

    <div class="space-y-4">
        @forelse ($categorias as $categoria)
            <div class="bg-white border border-gray-200 rounded">
                <div class="flex items-center justify-between px-4 py-3">
                    <span class="font-medium">{{ $categoria->nombre }}</span>
                    <div class="text-sm space-x-3">
                        <span class="text-gray-500">{{ $categoria->libros_count }} libro(s)</span>
                        <a href="{{ route('catalogo.categorias.edit', $categoria) }}" class="text-blue-700">Editar</a>
                        <form method="POST" action="{{ route('catalogo.categorias.destroy', $categoria) }}" class="inline"
                              onsubmit="return confirm('¿Eliminar esta categoría?');">
                            @csrf
                            @method('DELETE')
                            <button type="submit" class="text-red-700">Eliminar</button>
                        </form>
                    </div>
                </div>

                @if ($categoria->subcategorias->isNotEmpty())
                    <ul class="border-t border-gray-100 divide-y divide-gray-100">
                        @foreach ($categoria->subcategorias as $subcategoria)
                            <li class="flex items-center justify-between px-4 py-2 pl-8 text-sm">
                                <span>— {{ $subcategoria->nombre }}</span>
                                <div class="space-x-3">
                                    <a href="{{ route('catalogo.categorias.edit', $subcategoria) }}" class="text-blue-700">Editar</a>
                                    <form method="POST" action="{{ route('catalogo.categorias.destroy', $subcategoria) }}" class="inline"
                                          onsubmit="return confirm('¿Eliminar esta subcategoría?');">
                                        @csrf
                                        @method('DELETE')
                                        <button type="submit" class="text-red-700">Eliminar</button>
                                    </form>
                                </div>
                            </li>
                        @endforeach
                    </ul>
                @endif
            </div>
        @empty
            <p class="text-gray-500 text-sm">No hay categorías cargadas.</p>
        @endforelse
    </div>
@endsection
