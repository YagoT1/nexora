{{-- Origen: Plan de Implementación v2, Módulo 6 — Excepciones y restricciones. Criterio de
     aceptación: "Pantalla de listado de excepciones vigentes, con filtros por tipo y entidad."
     El estado mostrado es el derivado (ExcepcionAutorizada::estadoVisible(), Decisión D-15), no la
     columna cruda — ver BRIEFING-MODULO-6-EXCEPCIONES-RESTRICCIONES.md, Paso 3. --}}
@extends('layouts.app')

@section('titulo', 'Excepciones autorizadas')

@section('contenido')
    <div class="flex items-center justify-between mb-6">
        <h1 class="text-xl font-semibold">Excepciones autorizadas</h1>
        <a href="{{ route('excepciones.create') }}" class="rounded bg-gray-900 text-white text-sm px-4 py-2">
            Nueva excepción
        </a>
    </div>

    @if ($entidadIdFiltro !== '')
        <div class="mb-4 flex items-center justify-between rounded border border-blue-200 bg-blue-50 px-4 py-3 text-sm text-blue-800">
            <span>Mostrando solo las excepciones de la entidad seleccionada.</span>
            <a href="{{ route('excepciones.index', ['tipo' => $tipoFiltro]) }}" class="text-blue-700 underline">Ver todas</a>
        </div>
    @endif

    <form method="GET" action="{{ route('excepciones.index') }}" class="flex flex-wrap gap-3 mb-4 bg-white border border-gray-200 rounded p-4">
        @if ($entidadIdFiltro !== '')
            <input type="hidden" name="entidad_afectada_id" value="{{ $entidadIdFiltro }}">
        @endif
        <div>
            <label class="block text-xs text-gray-600 mb-1">Tipo</label>
            <select name="tipo" class="border-gray-300 rounded text-sm">
                <option value="">Todos</option>
                @foreach (\App\Models\ExcepcionAutorizada::ETIQUETAS_TIPO as $valor => $etiqueta)
                    <option value="{{ $valor }}" {{ $tipoFiltro === $valor ? 'selected' : '' }}>{{ $etiqueta }}</option>
                @endforeach
            </select>
        </div>

        <div>
            <label class="block text-xs text-gray-600 mb-1">Entidad afectada</label>
            <select name="entidad_afectada_type" class="border-gray-300 rounded text-sm">
                <option value="">Todas</option>
                <option value="{{ \App\Models\Socio::class }}" {{ $entidadFiltro === \App\Models\Socio::class ? 'selected' : '' }}>Socio</option>
                <option value="{{ \App\Models\Ejemplar::class }}" {{ $entidadFiltro === \App\Models\Ejemplar::class ? 'selected' : '' }}>Ejemplar</option>
            </select>
        </div>

        <div class="flex items-end">
            <button type="submit" class="rounded bg-gray-900 text-white text-sm px-4 py-2">Filtrar</button>
        </div>
    </form>

    <table class="w-full text-sm bg-white border border-gray-200 rounded">
        <thead class="bg-gray-100 text-left">
            <tr>
                <th class="px-4 py-2">Tipo</th>
                <th class="px-4 py-2">Entidad afectada</th>
                <th class="px-4 py-2">Autorizado por</th>
                <th class="px-4 py-2">Vigencia</th>
                <th class="px-4 py-2">Motivo</th>
                <th class="px-4 py-2">Estado</th>
                <th class="px-4 py-2"></th>
            </tr>
        </thead>
        <tbody>
            @forelse ($excepciones as $excepcion)
                <tr class="border-t border-gray-100">
                    <td class="px-4 py-2">{{ \App\Models\ExcepcionAutorizada::ETIQUETAS_TIPO[$excepcion->tipo] ?? $excepcion->tipo }}</td>
                    <td class="px-4 py-2">
                        @if ($excepcion->entidadAfectada instanceof \App\Models\Socio)
                            Socio: {{ $excepcion->entidadAfectada->nombre_principal }}
                        @elseif ($excepcion->entidadAfectada instanceof \App\Models\Ejemplar)
                            Ejemplar: {{ $excepcion->entidadAfectada->libro->titulo }} (#{{ $excepcion->entidadAfectada->id }})
                        @else
                            <span class="text-gray-400">(entidad eliminada)</span>
                        @endif
                    </td>
                    <td class="px-4 py-2">
                        {{ $excepcion->autorizadoPor->name ?? '—' }}
                        <span class="text-gray-500">{{ $excepcion->fecha_autorizacion->format('d/m/Y') }}</span>
                    </td>
                    <td class="px-4 py-2">
                        {{ $excepcion->fecha_inicio->format('d/m/Y') }}
                        –
                        {{ $excepcion->fecha_fin?->format('d/m/Y') ?? 'indefinida' }}
                    </td>
                    <td class="px-4 py-2 max-w-xs truncate" title="{{ $excepcion->motivo }}">{{ $excepcion->motivo }}</td>
                    <td class="px-4 py-2">
                        @php $estado = $excepcion->estadoVisible(); @endphp
                        <span @class([
                            'inline-block rounded px-2 py-0.5 text-xs',
                            'bg-green-100 text-green-800' => $estado === \App\Models\ExcepcionAutorizada::ESTADO_VIGENTE,
                            'bg-gray-100 text-gray-700' => $estado === \App\Models\ExcepcionAutorizada::ESTADO_VENCIDA,
                            'bg-red-100 text-red-800' => $estado === \App\Models\ExcepcionAutorizada::ESTADO_REVOCADA,
                        ])>
                            {{ ucfirst($estado) }}
                        </span>
                    </td>
                    <td class="px-4 py-2 text-right">
                        @if ($estado === \App\Models\ExcepcionAutorizada::ESTADO_VIGENTE)
                            <form method="POST" action="{{ route('excepciones.revocar', $excepcion) }}" class="inline"
                                  onsubmit="return confirm('¿Revocar esta excepción autorizada?');">
                                @csrf
                                @method('PATCH')
                                <button type="submit" class="text-red-700">Revocar</button>
                            </form>
                        @endif
                    </td>
                </tr>
            @empty
                <tr>
                    <td class="px-4 py-4 text-gray-500" colspan="7">No hay excepciones autorizadas cargadas.</td>
                </tr>
            @endforelse
        </tbody>
    </table>

    <div class="mt-4">{{ $excepciones->links() }}</div>
@endsection
