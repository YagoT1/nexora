<?php

// Origen: Plan de Implementación v2, Módulo 5 — Renovaciones y reservas. Ver
// Fase 6 - Development/BRIEFING-MODULO-5-RENOVACIONES-RESERVAS.md, CU-2, Paso 4.
// La Reserva se crea sobre un Libro (título), no sobre un Ejemplar puntual — el sistema asigna el
// ejemplar concreto más adelante, cuando uno queda libre (Libro::asignarSiguienteReserva(), Paso 2).

namespace App\Http\Controllers\Prestamos;

use App\Http\Controllers\Controller;
use App\Models\Libro;
use App\Models\Reserva;
use App\Models\Socio;
use Illuminate\Http\Request;

class ReservaController extends Controller
{
    /**
     * Flujo "buscar socio → confirmar", mismo criterio que PrestamoController::create() (sin
     * JavaScript de framework adicional, búsqueda reenviada como GET sobre esta misma pantalla).
     */
    public function create(Request $request, Libro $libro)
    {
        $socio = $request->filled('socio_id')
            ? Socio::with('tipoSocio')->findOrFail($request->integer('socio_id'))
            : null;

        $busquedaSocio = $request->string('busqueda_socio')->trim()->toString();
        $sociosEncontrados = collect();
        if (! $socio && $busquedaSocio !== '') {
            $sociosEncontrados = Socio::with('tipoSocio')
                ->buscar($busquedaSocio)
                ->orderBy('nombre_principal')
                ->limit(10)
                ->get();
        }

        $yaTieneReservaActiva = $socio && $this->socioYaTieneReservaActiva($libro, $socio);

        return view('prestamos.reservas.create', compact(
            'libro', 'socio', 'busquedaSocio', 'sociosEncontrados', 'yaTieneReservaActiva',
        ));
    }

    /**
     * Criterio de aceptación: "Un socio no puede tener dos reservas activas para el mismo Libro."
     * "Activa" = 'pendiente' o 'personal_alertado' (Reserva::ESTADOS_ACTIVOS) — una reserva ya
     * 'retirada', 'vencida_por_no_retiro' o 'cancelada' no cuenta, el socio puede volver a reservar.
     */
    public function store(Request $request, Libro $libro)
    {
        $datos = $request->validate([
            'socio_id' => ['required', 'exists:socios,id'],
        ]);

        $socio = Socio::findOrFail($datos['socio_id']);

        if ($this->socioYaTieneReservaActiva($libro, $socio)) {
            return back()->withInput()->withErrors([
                'socio_id' => 'Este socio ya tiene una reserva activa para este libro.',
            ]);
        }

        Reserva::create([
            'libro_id' => $libro->id,
            'socio_id' => $socio->id,
            'fecha_reserva' => now()->toDateString(),
            'estado' => Reserva::ESTADO_PENDIENTE,
        ]);

        return redirect()->route('catalogo.libros.show', $libro)
            ->with('status', "Reserva registrada para {$socio->nombre_principal}.");
    }

    private function socioYaTieneReservaActiva(Libro $libro, Socio $socio): bool
    {
        return Reserva::where('libro_id', $libro->id)
            ->where('socio_id', $socio->id)
            ->whereIn('estado', Reserva::ESTADOS_ACTIVOS)
            ->exists();
    }
}
