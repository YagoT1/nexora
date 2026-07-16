{{-- Origen: Plan de Implementación v2, Módulo 6 — Excepciones y restricciones, CU-1 del briefing.
     Flujo: elegir tipo -> buscar/seleccionar la entidad que corresponda a ese tipo (Socio o
     Ejemplar, ver ExcepcionAutorizada::ENTIDADES_POR_TIPO) -> confirmar motivo y vigencia. --}}
@extends('layouts.app')

@section('titulo', 'Nueva excepción autorizada')

@section('contenido')
    <h1 class="text-xl font-semibold mb-6">Nueva excepción autorizada</h1>

    {{-- Paso 1: tipo --}}
    @if ($tipo === '')
        <div class="bg-white border border-gray-200 rounded p-6 mb-6 max-w-md">
            <h2 class="text-sm font-semibold text-gray-700 mb-3">1. Elegir tipo de excepción</h2>
            <form method="GET" action="{{ route('excepciones.create') }}" class="space-y-4">
                <select name="tipo" class="w-full border-gray-300 rounded" required>
                    <option value="">Seleccionar…</option>
                    @foreach (\App\Models\ExcepcionAutorizada::ETIQUETAS_TIPO as $valor => $etiqueta)
                        <option value="{{ $valor }}">{{ $etiqueta }}</option>
                    @endforeach
                </select>
                <button type="submit" class="rounded bg-gray-900 text-white text-sm px-4 py-2">Continuar</button>
            </form>
        </div>
    @else
        <div class="bg-white border border-gray-200 rounded p-6 mb-6">
            <div class="flex items-center justify-between">
                <div>
                    <h2 class="text-sm font-semibold text-gray-700">Tipo</h2>
                    <p>{{ \App\Models\ExcepcionAutorizada::ETIQUETAS_TIPO[$tipo] ?? $tipo }}</p>
                </div>
                <a href="{{ route('excepciones.create') }}" class="text-sm text-gray-500">Cambiar tipo</a>
            </div>
        </div>
    @endif

    {{-- Paso 2: entidad afectada (Socio o Ejemplar, según el tipo elegido) --}}
    @if ($tipo !== '' && $entidadEsperada === \App\Models\Socio::class && ! $socio)
        <div class="bg-white border border-gray-200 rounded p-6 mb-6">
            <h2 class="text-sm font-semibold text-gray-700 mb-3">2. Buscar socio</h2>
            <form method="GET" action="{{ route('excepciones.create') }}" class="flex gap-2 mb-4">
                <input type="hidden" name="tipo" value="{{ $tipo }}">
                <input type="text" name="busqueda_socio" value="{{ $busquedaSocio }}" placeholder="Nombre del socio…" class="flex-1 border-gray-300 rounded">
                <button type="submit" class="rounded bg-gray-900 text-white text-sm px-4 py-2">Buscar</button>
            </form>

            @if ($busquedaSocio !== '')
                <table class="w-full text-sm border border-gray-200 rounded">
                    <tbody>
                        @forelse ($sociosEncontrados as $s)
                            <tr class="border-t border-gray-100">
                                <td class="px-4 py-2">{{ $s->nombre_principal }} <span class="text-gray-500">({{ $s->tipoSocio->nombre }})</span></td>
                                <td class="px-4 py-2 text-right">
                                    <a href="{{ route('excepciones.create', ['tipo' => $tipo, 'socio_id' => $s->id]) }}" class="text-blue-700">
                                        Seleccionar
                                    </a>
                                </td>
                            </tr>
                        @empty
                            <tr><td class="px-4 py-4 text-gray-500" colspan="2">Sin resultados.</td></tr>
                        @endforelse
                    </tbody>
                </table>
            @endif
        </div>
    @elseif ($tipo !== '' && $entidadEsperada === \App\Models\Socio::class && $socio)
        <div class="bg-white border border-gray-200 rounded p-6 mb-6">
            <div class="flex items-center justify-between">
                <div>
                    <h2 class="text-sm font-semibold text-gray-700">Socio</h2>
                    <p>{{ $socio->nombre_principal }} — {{ $socio->tipoSocio->nombre }}</p>
                </div>
                <a href="{{ route('excepciones.create', ['tipo' => $tipo]) }}" class="text-sm text-gray-500">Cambiar socio</a>
            </div>
        </div>
    @endif

    @if ($tipo !== '' && $entidadEsperada === \App\Models\Ejemplar::class && ! $ejemplar)
        <div class="bg-white border border-gray-200 rounded p-6 mb-6">
            <h2 class="text-sm font-semibold text-gray-700 mb-3">2. Buscar ejemplar</h2>
            <form method="GET" action="{{ route('excepciones.create') }}" class="flex gap-2 mb-4">
                <input type="hidden" name="tipo" value="{{ $tipo }}">
                <input type="text" name="busqueda_libro" value="{{ $busquedaLibro }}" placeholder="Título del libro…" class="flex-1 border-gray-300 rounded">
                <button type="submit" class="rounded bg-gray-900 text-white text-sm px-4 py-2">Buscar</button>
            </form>

            @if ($busquedaLibro !== '')
                <table class="w-full text-sm border border-gray-200 rounded">
                    <tbody>
                        @forelse ($ejemplaresEncontrados as $e)
                            <tr class="border-t border-gray-100">
                                <td class="px-4 py-2">{{ $e->libro->titulo }} <span class="text-gray-500">(ejemplar #{{ $e->id }})</span></td>
                                <td class="px-4 py-2 text-right">
                                    <a href="{{ route('excepciones.create', ['tipo' => $tipo, 'ejemplar_id' => $e->id]) }}" class="text-blue-700">
                                        Seleccionar
                                    </a>
                                </td>
                            </tr>
                        @empty
                            <tr><td class="px-4 py-4 text-gray-500" colspan="2">Sin resultados.</td></tr>
                        @endforelse
                    </tbody>
                </table>
            @endif
        </div>
    @elseif ($tipo !== '' && $entidadEsperada === \App\Models\Ejemplar::class && $ejemplar)
        <div class="bg-white border border-gray-200 rounded p-6 mb-6">
            <div class="flex items-center justify-between">
                <div>
                    <h2 class="text-sm font-semibold text-gray-700">Ejemplar</h2>
                    <p>{{ $ejemplar->libro->titulo }} (#{{ $ejemplar->id }})</p>
                </div>
                <a href="{{ route('excepciones.create', ['tipo' => $tipo]) }}" class="text-sm text-gray-500">Cambiar ejemplar</a>
            </div>
        </div>
    @endif

    {{-- Paso 3: confirmar --}}
    @if (($socio && $entidadEsperada === \App\Models\Socio::class) || ($ejemplar && $entidadEsperada === \App\Models\Ejemplar::class))
        <div class="bg-white border border-gray-200 rounded p-6 max-w-md">
            <h2 class="text-sm font-semibold text-gray-700 mb-3">3. Confirmar excepción</h2>
            <form method="POST" action="{{ route('excepciones.store') }}" class="space-y-4">
                @csrf
                <input type="hidden" name="tipo" value="{{ $tipo }}">
                <input type="hidden" name="entidad_id" value="{{ $socio->id ?? $ejemplar->id }}">

                <div>
                    <label class="block text-sm mb-1">Motivo</label>
                    <textarea name="motivo" class="w-full border-gray-300 rounded" rows="3" required>{{ old('motivo') }}</textarea>
                    @error('motivo') <p class="text-red-600 text-xs mt-1">{{ $message }}</p> @enderror
                </div>

                <div>
                    <label class="block text-sm mb-1">Fecha de inicio</label>
                    <input type="date" name="fecha_inicio" value="{{ old('fecha_inicio', now()->toDateString()) }}" class="w-full border-gray-300 rounded" required>
                    @error('fecha_inicio') <p class="text-red-600 text-xs mt-1">{{ $message }}</p> @enderror
                </div>

                <div>
                    <label class="block text-sm mb-1">Fecha de fin (opcional — vacía = indefinida hasta revocación, RN-11)</label>
                    <input type="date" name="fecha_fin" value="{{ old('fecha_fin') }}" class="w-full border-gray-300 rounded">
                    @error('fecha_fin') <p class="text-red-600 text-xs mt-1">{{ $message }}</p> @enderror
                </div>

                @error('entidad_id') <p class="text-red-600 text-sm">{{ $message }}</p> @enderror

                <button type="submit" class="rounded bg-gray-900 text-white text-sm px-4 py-2">Crear excepción</button>
            </form>
        </div>
    @endif
@endsection
