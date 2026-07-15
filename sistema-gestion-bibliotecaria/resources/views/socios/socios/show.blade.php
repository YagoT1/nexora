{{-- Criterios de aceptación Módulo 3: vista de mostrador (préstamos activos, reservas activas,
     restricción vigente, atrasos en 12 meses) e historial de préstamos paginado. --}}
@extends('layouts.app')

@section('titulo', $socio->nombre_principal)

@section('contenido')
    <div class="flex items-center justify-between mb-6">
        <div>
            <h1 class="text-xl font-semibold">{{ $socio->nombre_principal }}</h1>
            <p class="text-sm text-gray-500">
                {{ $socio->tipoSocio->nombre }} ·
                {{ $socio->estado === 'activo' ? 'Activo' : 'Inactivo' }}
                @if ($socio->dni) · DNI {{ $socio->dni }} @endif
            </p>
        </div>
        <div class="space-x-3">
            {{-- Origen: Módulo 4 — Préstamos, Paso 4. Punto de entrada natural al registro de un
                 préstamo: el personal ya está viendo el estado del socio (restricción, límite)
                 antes de decidir continuar. --}}
            <a href="{{ route('prestamos.create', ['socio_id' => $socio->id]) }}" class="text-sm text-green-700">Registrar préstamo</a>
            <a href="{{ route('socios.socios.edit', $socio) }}" class="text-sm text-blue-700">Editar</a>
            <a href="{{ route('socios.socios.index') }}" class="text-sm text-gray-500">Volver al listado</a>
        </div>
    </div>

    @if ($restriccionVigente)
        <div class="mb-6 rounded border border-red-300 bg-red-50 px-4 py-3 text-sm text-red-800">
            <strong>Restricción vigente</strong> hasta {{ $restriccionVigente->fecha_fin->format('d/m/Y') }}
            ({{ $restriccionVigente->tipo }}{{ $restriccionVigente->observaciones ? ' — '.$restriccionVigente->observaciones : '' }}).
        </div>
    @endif

    <div class="grid grid-cols-1 sm:grid-cols-3 gap-4 mb-8">
        <div class="bg-white border border-gray-200 rounded p-4">
            <p class="text-xs text-gray-500 uppercase">Préstamos activos</p>
            <p class="text-2xl font-semibold">{{ $prestamosActivos->count() }}
                <span class="text-sm font-normal text-gray-500">/ {{ $socio->tipoSocio->limite_prestamos_simultaneos }}</span>
            </p>
        </div>
        <div class="bg-white border border-gray-200 rounded p-4">
            <p class="text-xs text-gray-500 uppercase">Reservas activas</p>
            <p class="text-2xl font-semibold">{{ $reservasActivas->count() }}</p>
        </div>
        <div class="bg-white border border-gray-200 rounded p-4">
            <p class="text-xs text-gray-500 uppercase">Atrasos (últimos 12 meses)</p>
            <p class="text-2xl font-semibold">{{ $atrasosUltimos12Meses }}</p>
        </div>
    </div>

    <h2 class="text-sm font-semibold text-gray-700 mb-3">Préstamos activos</h2>
    <table class="w-full text-sm bg-white border border-gray-200 rounded mb-8">
        <thead class="bg-gray-100 text-left">
            <tr>
                <th class="px-4 py-2">Libro</th>
                <th class="px-4 py-2">Fecha de préstamo</th>
                <th class="px-4 py-2">Vencimiento</th>
                <th class="px-4 py-2">Estado</th>
            </tr>
        </thead>
        <tbody>
            @forelse ($prestamosActivos as $prestamo)
                <tr class="border-t border-gray-100">
                    <td class="px-4 py-2">{{ $prestamo->ejemplar->libro->titulo }}</td>
                    <td class="px-4 py-2">{{ $prestamo->fecha_prestamo->format('d/m/Y') }}</td>
                    <td class="px-4 py-2">{{ $prestamo->fecha_vencimiento->format('d/m/Y') }}</td>
                    <td class="px-4 py-2">{{ $prestamo->estado === 'atrasado' ? 'Atrasado' : 'Activo' }}</td>
                </tr>
            @empty
                <tr><td class="px-4 py-4 text-gray-500" colspan="4">Sin préstamos activos.</td></tr>
            @endforelse
        </tbody>
    </table>

    <h2 class="text-sm font-semibold text-gray-700 mb-3">Reservas activas</h2>
    <table class="w-full text-sm bg-white border border-gray-200 rounded mb-8">
        <thead class="bg-gray-100 text-left">
            <tr>
                <th class="px-4 py-2">Libro</th>
                <th class="px-4 py-2">Fecha de reserva</th>
                <th class="px-4 py-2">Estado</th>
            </tr>
        </thead>
        <tbody>
            @forelse ($reservasActivas as $reserva)
                <tr class="border-t border-gray-100">
                    <td class="px-4 py-2">{{ $reserva->libro->titulo }}</td>
                    <td class="px-4 py-2">{{ $reserva->fecha_reserva->format('d/m/Y') }}</td>
                    <td class="px-4 py-2">{{ $reserva->estado === 'personal_alertado' ? 'Personal alertado' : 'Pendiente' }}</td>
                </tr>
            @empty
                <tr><td class="px-4 py-4 text-gray-500" colspan="3">Sin reservas activas.</td></tr>
            @endforelse
        </tbody>
    </table>

    <h2 class="text-sm font-semibold text-gray-700 mb-3">Historial de préstamos</h2>
    <table class="w-full text-sm bg-white border border-gray-200 rounded">
        <thead class="bg-gray-100 text-left">
            <tr>
                <th class="px-4 py-2">Libro</th>
                <th class="px-4 py-2">Fecha de préstamo</th>
                <th class="px-4 py-2">Devolución</th>
                <th class="px-4 py-2">Estado</th>
            </tr>
        </thead>
        <tbody>
            @forelse ($historialPrestamos as $prestamo)
                <tr class="border-t border-gray-100">
                    <td class="px-4 py-2">{{ $prestamo->ejemplar->libro->titulo }}</td>
                    <td class="px-4 py-2">{{ $prestamo->fecha_prestamo->format('d/m/Y') }}</td>
                    <td class="px-4 py-2">{{ $prestamo->fecha_devolucion_efectiva?->format('d/m/Y') ?? '—' }}</td>
                    <td class="px-4 py-2">{{ ucfirst($prestamo->estado) }}</td>
                </tr>
            @empty
                <tr><td class="px-4 py-4 text-gray-500" colspan="4">Sin préstamos registrados.</td></tr>
            @endforelse
        </tbody>
    </table>

    <div class="mt-4">{{ $historialPrestamos->links() }}</div>
@endsection
