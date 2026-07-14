{{-- Criterio de aceptación Módulo 3: CRUD de Tipo de Socio, D-04 (configuración editable). --}}
@extends('layouts.app')

@section('titulo', 'Tipos de socio')

@section('contenido')
    @include('socios._subnav')

    <div class="flex items-center justify-between mb-6">
        <h1 class="text-xl font-semibold">Tipos de socio</h1>
        <a href="{{ route('socios.tipos-socio.create') }}" class="rounded bg-gray-900 text-white text-sm px-4 py-2">
            Nuevo tipo de socio
        </a>
    </div>

    <table class="w-full text-sm bg-white border border-gray-200 rounded">
        <thead class="bg-gray-100 text-left">
            <tr>
                <th class="px-4 py-2">Nombre</th>
                <th class="px-4 py-2">Límite de préstamos simultáneos</th>
                <th class="px-4 py-2">Restricción automática</th>
                <th class="px-4 py-2">Socios</th>
                <th class="px-4 py-2"></th>
            </tr>
        </thead>
        <tbody>
            @forelse ($tiposSocio as $tipoSocio)
                <tr class="border-t border-gray-100">
                    <td class="px-4 py-2">{{ $tipoSocio->nombre }}</td>
                    <td class="px-4 py-2">{{ $tipoSocio->limite_prestamos_simultaneos }}</td>
                    <td class="px-4 py-2">{{ $tipoSocio->sujeto_a_restriccion_automatica ? 'Sí' : 'No' }}</td>
                    <td class="px-4 py-2">{{ $tipoSocio->socios_count }}</td>
                    <td class="px-4 py-2 text-right space-x-3">
                        <a href="{{ route('socios.tipos-socio.edit', $tipoSocio) }}" class="text-blue-700">Editar</a>
                        <form method="POST" action="{{ route('socios.tipos-socio.destroy', $tipoSocio) }}" class="inline"
                              onsubmit="return confirm('¿Eliminar este tipo de socio?');">
                            @csrf
                            @method('DELETE')
                            <button type="submit" class="text-red-700">Eliminar</button>
                        </form>
                    </td>
                </tr>
            @empty
                <tr>
                    <td class="px-4 py-4 text-gray-500" colspan="5">No hay tipos de socio cargados.</td>
                </tr>
            @endforelse
        </tbody>
    </table>

    <div class="mt-4">{{ $tiposSocio->links() }}</div>
@endsection
