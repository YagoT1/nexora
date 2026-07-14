@extends('layouts.app')

@section('titulo', 'Editar socio')

@section('contenido')
    <h1 class="text-xl font-semibold mb-6">Editar socio</h1>

    <form method="POST" action="{{ route('socios.socios.update', $socio) }}" class="max-w-md space-y-4 bg-white p-6 border border-gray-200 rounded">
        @csrf
        @method('PUT')

        <div>
            <label class="block text-sm mb-1">Nombre principal</label>
            <input type="text" name="nombre_principal" value="{{ old('nombre_principal', $socio->nombre_principal) }}" class="w-full border-gray-300 rounded" required>
            @error('nombre_principal') <p class="text-red-600 text-xs mt-1">{{ $message }}</p> @enderror
        </div>

        <div>
            <label class="block text-sm mb-1">Nombres alternativos (opcional, uno por línea)</label>
            <textarea name="nombres_alternativos" class="w-full border-gray-300 rounded" rows="3">{{ old('nombres_alternativos', implode("\n", $socio->nombres_alternativos ?? [])) }}</textarea>
            @error('nombres_alternativos') <p class="text-red-600 text-xs mt-1">{{ $message }}</p> @enderror
        </div>

        <div>
            <label class="block text-sm mb-1">DNI (opcional)</label>
            <input type="text" name="dni" value="{{ old('dni', $socio->dni) }}" class="w-full border-gray-300 rounded">
            @error('dni') <p class="text-red-600 text-xs mt-1">{{ $message }}</p> @enderror
        </div>

        <div>
            <label class="block text-sm mb-1">Email (opcional)</label>
            <input type="email" name="email" value="{{ old('email', $socio->email) }}" class="w-full border-gray-300 rounded">
            @error('email') <p class="text-red-600 text-xs mt-1">{{ $message }}</p> @enderror
        </div>

        <div>
            <label class="block text-sm mb-1">Teléfono (opcional)</label>
            <input type="text" name="telefono" value="{{ old('telefono', $socio->telefono) }}" class="w-full border-gray-300 rounded">
            @error('telefono') <p class="text-red-600 text-xs mt-1">{{ $message }}</p> @enderror
        </div>

        <div>
            <label class="block text-sm mb-1">Fecha de alta</label>
            <input type="date" name="fecha_alta" value="{{ old('fecha_alta', $socio->fecha_alta->toDateString()) }}" class="w-full border-gray-300 rounded" required>
            @error('fecha_alta') <p class="text-red-600 text-xs mt-1">{{ $message }}</p> @enderror
        </div>

        <div>
            <label class="block text-sm mb-1">Estado</label>
            <select name="estado" class="w-full border-gray-300 rounded" required>
                <option value="activo" {{ old('estado', $socio->estado) === 'activo' ? 'selected' : '' }}>Activo</option>
                <option value="inactivo" {{ old('estado', $socio->estado) === 'inactivo' ? 'selected' : '' }}>Inactivo</option>
            </select>
            @error('estado') <p class="text-red-600 text-xs mt-1">{{ $message }}</p> @enderror
        </div>

        <div>
            <label class="block text-sm mb-1">Tipo de socio</label>
            <select name="tipo_socio_id" class="w-full border-gray-300 rounded" required>
                @foreach ($tiposSocio as $tipoSocio)
                    <option value="{{ $tipoSocio->id }}" {{ (int) old('tipo_socio_id', $socio->tipo_socio_id) === $tipoSocio->id ? 'selected' : '' }}>
                        {{ $tipoSocio->nombre }}
                    </option>
                @endforeach
            </select>
            @error('tipo_socio_id') <p class="text-red-600 text-xs mt-1">{{ $message }}</p> @enderror
        </div>

        <div class="flex items-center justify-between">
            <button type="submit" class="rounded bg-gray-900 text-white text-sm px-4 py-2">Guardar cambios</button>
            <a href="{{ route('socios.socios.show', $socio) }}" class="text-sm text-blue-700">Ver detalle del socio →</a>
        </div>
    </form>
@endsection
