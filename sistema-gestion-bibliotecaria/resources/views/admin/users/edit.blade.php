@extends('layouts.app')

@section('titulo', 'Editar usuario')

@section('contenido')
    <h1 class="text-xl font-semibold mb-6">Editar usuario</h1>

    <form method="POST" action="{{ route('admin.users.update', $usuario) }}" class="max-w-md space-y-4 bg-white p-6 border border-gray-200 rounded">
        @csrf
        @method('PUT')

        <div>
            <label class="block text-sm mb-1">Nombre</label>
            <input type="text" name="name" value="{{ old('name', $usuario->name) }}" class="w-full border-gray-300 rounded" required>
            @error('name') <p class="text-red-600 text-xs mt-1">{{ $message }}</p> @enderror
        </div>

        <div>
            <label class="block text-sm mb-1">Email</label>
            <input type="email" name="email" value="{{ old('email', $usuario->email) }}" class="w-full border-gray-300 rounded" required>
            @error('email') <p class="text-red-600 text-xs mt-1">{{ $message }}</p> @enderror
        </div>

        <div>
            <label class="block text-sm mb-1">Rol</label>
            <select name="rol" class="w-full border-gray-300 rounded" required>
                @foreach ($roles as $rol)
                    <option value="{{ $rol }}" {{ old('rol', $usuario->rol) === $rol ? 'selected' : '' }}>{{ ucfirst($rol) }}</option>
                @endforeach
            </select>
        </div>

        <div>
            <label class="block text-sm mb-1">Estado</label>
            <select name="estado" class="w-full border-gray-300 rounded" required>
                <option value="activo" {{ old('estado', $usuario->estado) === 'activo' ? 'selected' : '' }}>Activo</option>
                <option value="inactivo" {{ old('estado', $usuario->estado) === 'inactivo' ? 'selected' : '' }}>Inactivo</option>
            </select>
        </div>

        <button type="submit" class="rounded bg-gray-900 text-white text-sm px-4 py-2">Guardar cambios</button>
    </form>
@endsection
