@extends('layouts.app')

@section('titulo', 'Nuevo usuario')

@section('contenido')
    <h1 class="text-xl font-semibold mb-6">Nuevo usuario</h1>

    <form method="POST" action="{{ route('admin.users.store') }}" class="max-w-md space-y-4 bg-white p-6 border border-gray-200 rounded">
        @csrf

        <div>
            <label class="block text-sm mb-1">Nombre</label>
            <input type="text" name="name" value="{{ old('name') }}" class="w-full border-gray-300 rounded" required>
            @error('name') <p class="text-red-600 text-xs mt-1">{{ $message }}</p> @enderror
        </div>

        <div>
            <label class="block text-sm mb-1">Email</label>
            <input type="email" name="email" value="{{ old('email') }}" class="w-full border-gray-300 rounded" required>
            @error('email') <p class="text-red-600 text-xs mt-1">{{ $message }}</p> @enderror
        </div>

        <div>
            <label class="block text-sm mb-1">Contraseña</label>
            <input type="password" name="password" class="w-full border-gray-300 rounded" required>
            @error('password') <p class="text-red-600 text-xs mt-1">{{ $message }}</p> @enderror
        </div>

        <div>
            <label class="block text-sm mb-1">Confirmar contraseña</label>
            <input type="password" name="password_confirmation" class="w-full border-gray-300 rounded" required>
        </div>

        <div>
            <label class="block text-sm mb-1">Rol</label>
            <select name="rol" class="w-full border-gray-300 rounded" required>
                @foreach ($roles as $rol)
                    <option value="{{ $rol }}" {{ old('rol') === $rol ? 'selected' : '' }}>{{ ucfirst($rol) }}</option>
                @endforeach
            </select>
        </div>

        <button type="submit" class="rounded bg-gray-900 text-white text-sm px-4 py-2">Crear usuario</button>
    </form>
@endsection
