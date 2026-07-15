{{-- Origen: Plan de Implementación v2, Módulo 4 — devolución (RN-12: no requiere identificar
     al socio, solo el ejemplar/libro). --}}
@extends('layouts.app')

@section('titulo', 'Registrar devolución')

@section('contenido')
    <h1 class="text-xl font-semibold mb-6">Registrar devolución</h1>

    @if (session('alertas'))
        @foreach (session('alertas') as $alerta)
            <div class="mb-4 rounded border border-amber-300 bg-amber-50 px-4 py-3 text-sm text-amber-800">
                {{ $alerta }}
            </div>
        @endforeach
    @endif

    {{-- Módulo 5, Paso 5 (RN-05): reservas ya alertadas al personal, con su fecha límite de retiro.
         Ver PrestamoController::buscarDevolucion() sobre por qué esta pantalla hace de "panel del
         mostrador" hasta que exista el Módulo 8. --}}
    @if ($reservasParaRetirar->isNotEmpty())
        <div class="bg-white border border-gray-200 rounded p-6 mb-6">
            <h2 class="text-sm font-semibold text-gray-700 mb-3">Reservas para retirar</h2>
            <table class="w-full text-sm border border-gray-200 rounded">
                <thead class="bg-gray-100 text-left">
                    <tr>
                        <th class="px-4 py-2">Libro</th>
                        <th class="px-4 py-2">Socio</th>
                        <th class="px-4 py-2">Retirar antes de</th>
                    </tr>
                </thead>
                <tbody>
                    @foreach ($reservasParaRetirar as $reserva)
                        <tr class="border-t border-gray-100">
                            <td class="px-4 py-2">{{ $reserva->libro->titulo }}</td>
                            <td class="px-4 py-2">{{ $reserva->socio->nombre_principal }}</td>
                            <td class="px-4 py-2">{{ $reserva->fecha_limite_retiro->format('d/m/Y H:i') }}</td>
                        </tr>
                    @endforeach
                </tbody>
            </table>
        </div>
    @endif

    <div class="bg-white border border-gray-200 rounded p-6">
        <form method="GET" action="{{ route('prestamos.devolucion.buscar') }}" class="flex gap-2 mb-4">
            <input type="text" name="busqueda" value="{{ $busqueda }}" placeholder="Título del libro…" class="flex-1 border-gray-300 rounded">
            <button type="submit" class="rounded bg-gray-900 text-white text-sm px-4 py-2">Buscar</button>
        </form>

        @if ($busqueda !== '')
            <table class="w-full text-sm border border-gray-200 rounded">
                <thead class="bg-gray-100 text-left">
                    <tr>
                        <th class="px-4 py-2">Libro</th>
                        <th class="px-4 py-2">Socio</th>
                        <th class="px-4 py-2">Vencimiento</th>
                        <th class="px-4 py-2"></th>
                    </tr>
                </thead>
                <tbody>
                    @forelse ($prestamosActivos as $prestamo)
                        <tr class="border-t border-gray-100">
                            <td class="px-4 py-2">{{ $prestamo->ejemplar->libro->titulo }} (#{{ $prestamo->ejemplar->id }})</td>
                            <td class="px-4 py-2">{{ $prestamo->socio->nombre_principal }}</td>
                            <td class="px-4 py-2">{{ $prestamo->fecha_vencimiento->format('d/m/Y') }}</td>
                            <td class="px-4 py-2 text-right">
                                <a href="{{ route('prestamos.devolucion.confirmar', $prestamo) }}" class="text-blue-700">Devolver</a>
                            </td>
                        </tr>
                    @empty
                        <tr><td class="px-4 py-4 text-gray-500" colspan="4">Sin préstamos activos para ese título.</td></tr>
                    @endforelse
                </tbody>
            </table>
        @endif
    </div>
@endsection
