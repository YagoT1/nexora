{{-- Criterio de aceptación Módulo 1: el Administrador puede crear, editar e inactivar usuarios
     y asignar rol. --}}
@extends('layouts.app')

@section('titulo', 'Administración de usuarios')

@section('contenido')
    <div class="flex items-center justify-between mb-6">
        <h1 class="text-xl font-semibold">Usuarios del sistema</h1>
        <a href="{{ route('admin.users.create') }}" class="rounded bg-gray-900 text-white text-sm px-4 py-2">
            Nuevo usuario
        </a>
    </div>

    <table class="w-full text-sm bg-white border border-gray-200 rounded">
        <thead class="bg-gray-100 text-left">
            <tr>
                <th class="px-4 py-2">Nombre</th>
                <th class="px-4 py-2">Email</th>
                <th class="px-4 py-2">Rol</th>
                <th class="px-4 py-2">Estado</th>
                <th class="px-4 py-2"></th>
            </tr>
        </thead>
        <tbody>
            @foreach ($usuarios as $usuario)
                <tr class="border-t border-gray-100">
                    <td class="px-4 py-2">{{ $usuario->name }}</td>
                    <td class="px-4 py-2">{{ $usuario->email }}</td>
                    <td class="px-4 py-2 capitalize">{{ $usuario->rol }}</td>
                    <td class="px-4 py-2">
                        <span class="{{ $usuario->estado === 'activo' ? 'text-green-700' : 'text-gray-400' }}">
                            {{ $usuario->estado }}
                        </span>
                    </td>
                    <td class="px-4 py-2 text-right space-x-3">
                        <a href="{{ route('admin.users.edit', $usuario) }}" class="text-blue-700">Editar</a>
                        @if ($usuario->estado === 'activo')
                            <form method="POST" action="{{ route('admin.users.inactivar', $usuario) }}" class="inline">
                                @csrf
                                @method('PATCH')
                                <button type="submit" class="text-red-700">Inactivar</button>
                            </form>
                        @else
                            <form method="POST" action="{{ route('admin.users.reactivar', $usuario) }}" class="inline">
                                @csrf
                                @method('PATCH')
                                <button type="submit" class="text-green-700">Reactivar</button>
                            </form>
                        @endif
                    </td>
                </tr>
            @endforeach
        </tbody>
    </table>

    <div class="mt-4">{{ $usuarios->links() }}</div>
@endsection
