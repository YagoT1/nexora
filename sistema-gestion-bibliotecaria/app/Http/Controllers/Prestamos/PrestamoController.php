<?php

// Origen: Plan de Implementación v2, Módulo 4 — Préstamos y devoluciones. Ver
// Fase 6 - Development/BRIEFING-MODULO-4-PRESTAMOS.md para el detalle de reglas, riesgos y plan.

namespace App\Http\Controllers\Prestamos;

use App\Http\Controllers\Controller;
use App\Models\Ejemplar;
use App\Models\ExcepcionAutorizada;
use App\Models\HistorialAtraso;
use App\Models\ParametroConfiguracion;
use App\Models\PrestamoDomiciliario;
use App\Models\Reserva;
use App\Models\RestriccionSocio;
use App\Models\Socio;
use Carbon\Carbon;
use Illuminate\Database\QueryException;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;

class PrestamoController extends Controller
{
    /**
     * Origen: Paso 2 del briefing. Flujo "buscar socio → verificar estado → seleccionar ejemplar →
     * confirmar" (Plan de Implementación v2, Módulo 4). El socio y el ejemplar pueden llegar ya
     * elegidos por query string (desde socios.socios.show o catalogo.libros.show) o buscarse acá
     * mismo — sin JavaScript de framework adicional (mismo criterio YAGNI que el resto del
     * proyecto), reenviando la búsqueda como GET sobre esta misma pantalla.
     */
    public function create(Request $request)
    {
        $socio = $request->filled('socio_id')
            ? Socio::with('tipoSocio')->findOrFail($request->integer('socio_id'))
            : null;

        $ejemplar = $request->filled('ejemplar_id')
            ? Ejemplar::with('libro')->findOrFail($request->integer('ejemplar_id'))
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

        $busquedaLibro = $request->string('busqueda_libro')->trim()->toString();
        $ejemplaresEncontrados = collect();
        if (! $ejemplar && $busquedaLibro !== '') {
            $ejemplaresEncontrados = Ejemplar::with('libro')
                ->whereHas('libro', fn ($q) => $q->whereRaw('unaccent(titulo) ILIKE unaccent(?)', ["%{$busquedaLibro}%"]))
                ->get()
                ->filter(fn (Ejemplar $e) => $e->estadoActual() === Ejemplar::ESTADO_DISPONIBLE && $e->puedeSalirDeLaBiblioteca())
                ->values();
        }

        $restriccionVigente = null;
        $cantidadPrestamosActivos = 0;
        if ($socio) {
            $restriccionVigente = $socio->restricciones()->get()->first(fn ($r) => $r->estaActiva());
            $cantidadPrestamosActivos = $socio->cantidadPrestamosActivos();
        }

        return view('prestamos.create', compact(
            'socio', 'ejemplar',
            'busquedaSocio', 'sociosEncontrados',
            'busquedaLibro', 'ejemplaresEncontrados',
            'restriccionVigente', 'cantidadPrestamosActivos',
        ));
    }

    public function store(Request $request)
    {
        $datos = $request->validate([
            'socio_id' => ['required', 'exists:socios,id'],
            'ejemplar_id' => ['required', 'exists:ejemplares,id'],
            'fecha_prestamo' => ['required', 'date'],
            'motivo_excepcion_limite' => ['nullable', 'string'],
        ]);

        $socio = Socio::with('tipoSocio')->findOrFail($datos['socio_id']);
        $ejemplar = Ejemplar::findOrFail($datos['ejemplar_id']);

        // RN-04, Nivel 2 de DA-09: verificación cruzada en la capa de aplicación, antes de intentar
        // la operación (el índice único parcial de la base de datos, Nivel 1, es la salvaguarda
        // final ante una carrera real — ver el catch de QueryException más abajo).
        if ($ejemplar->tieneMovimientoActivo()) {
            return back()->withInput()->withErrors([
                'ejemplar_id' => 'Este ejemplar ya tiene un movimiento activo (préstamo, custodia externa o movimiento interno) y no puede prestarse.',
            ]);
        }

        // RN-08 / RN-09.
        if (! $ejemplar->puedeSalirDeLaBiblioteca()) {
            return back()->withInput()->withErrors([
                'ejemplar_id' => 'La modalidad de acceso de este ejemplar no permite su salida de la biblioteca.',
            ]);
        }

        // RN-06: solo una Excepción Autorizada vigente de tipo "Exención" habilita el préstamo a un
        // socio con restricción activa — no es salteable con un motivo de texto libre (a diferencia
        // de RN-01, más abajo).
        $restriccionVigente = $socio->restricciones()->get()->first(fn ($r) => $r->estaActiva());
        $tieneExcepcionDeExencion = false;
        if ($restriccionVigente) {
            $tieneExcepcionDeExencion = $this->tieneExcepcionVigente($socio, ExcepcionAutorizada::TIPO_EXENCION_RESTRICCION);

            if (! $tieneExcepcionDeExencion) {
                return back()->withInput()->withErrors([
                    'socio_id' => "El socio tiene una restricción activa hasta el {$restriccionVigente->fecha_fin->format('d/m/Y')}"
                        .($restriccionVigente->observaciones ? " ({$restriccionVigente->observaciones})" : '')
                        .'. No puede recibir un nuevo préstamo, salvo una Excepción Autorizada vigente de tipo Exención.',
                ]);
            }
        }

        // RN-01: alerta, no bloqueo — el límite es el del Tipo de Socio, nunca un valor hardcodeado
        // (ver riesgo R-3 del briefing: los parámetros globales de límite no se usan acá).
        $cantidadActivos = $socio->cantidadPrestamosActivos();
        $limite = $socio->tipoSocio->limite_prestamos_simultaneos;
        $esExcepcionDeLimite = false;
        if ($cantidadActivos >= $limite) {
            if (blank($datos['motivo_excepcion_limite'] ?? null)) {
                return back()->withInput()->withErrors([
                    'motivo_excepcion_limite' => "El socio ya tiene {$cantidadActivos} préstamo(s) activo(s) (límite de su tipo: {$limite}). Para continuar, completá el motivo de la excepción.",
                ]);
            }
            $esExcepcionDeLimite = true;
        }

        // RN-02: vencimiento calculado a partir del plazo configurable (D-04) — nunca hardcodeado.
        $plazoDias = (int) ParametroConfiguracion::obtener(ParametroConfiguracion::PLAZO_PRESTAMO_DIAS, 15);
        $fechaPrestamo = Carbon::parse($datos['fecha_prestamo']);

        try {
            $prestamo = DB::transaction(function () use ($ejemplar, $socio, $fechaPrestamo, $plazoDias, $esExcepcionDeLimite, $datos, $tieneExcepcionDeExencion) {
                return PrestamoDomiciliario::create([
                    'ejemplar_id' => $ejemplar->id,
                    'socio_id' => $socio->id,
                    'fecha_registro' => now(),
                    // RN-13: fecha de préstamo editable, no necesariamente igual a fecha_registro.
                    'fecha_prestamo' => $fechaPrestamo->toDateString(),
                    'fecha_vencimiento' => $fechaPrestamo->copy()->addDays($plazoDias)->toDateString(),
                    'estado' => PrestamoDomiciliario::ESTADO_ACTIVO,
                    'registrado_por' => auth()->id(),
                    'es_excepcion_de_limite' => $esExcepcionDeLimite,
                    'motivo_excepcion_limite' => $esExcepcionDeLimite ? $datos['motivo_excepcion_limite'] : ($tieneExcepcionDeExencion ? 'Excepción de restricción aplicada.' : null),
                ]);
            });
        } catch (QueryException $e) {
            // RN-04, Nivel 1 de DA-09: el índice único parcial rechazó la operación — una carrera de
            // concurrencia real que el chequeo de Nivel 2 (arriba) no llegó a detectar a tiempo.
            if (str_contains($e->getMessage(), 'prestamos_domiciliarios_ejemplar_activo_unique')) {
                return back()->withInput()->withErrors([
                    'ejemplar_id' => 'Este ejemplar acaba de ser prestado por otra persona. Actualizá la página e intentá de nuevo.',
                ]);
            }

            throw $e;
        }

        return redirect()->route('socios.socios.show', $socio)
            ->with('status', "Préstamo de \"{$ejemplar->libro->titulo}\" registrado correctamente.");
    }

    /**
     * Origen: Paso 3 del briefing. RN-12: no se identifica quién trae el libro — solo el ejemplar.
     */
    public function buscarDevolucion(Request $request)
    {
        $busqueda = $request->string('busqueda')->trim()->toString();

        $prestamosActivos = collect();
        if ($busqueda !== '') {
            $prestamosActivos = PrestamoDomiciliario::query()
                ->whereIn('estado', PrestamoDomiciliario::ESTADOS_ABIERTOS)
                ->whereHas('ejemplar.libro', fn ($q) => $q->whereRaw('unaccent(titulo) ILIKE unaccent(?)', ["%{$busqueda}%"]))
                ->with(['ejemplar.libro', 'socio'])
                ->get();
        }

        return view('prestamos.devolucion-buscar', compact('busqueda', 'prestamosActivos'));
    }

    public function confirmarDevolucion(PrestamoDomiciliario $prestamo)
    {
        abort_unless(in_array($prestamo->estado, PrestamoDomiciliario::ESTADOS_ABIERTOS, true), 404);

        $prestamo->load(['ejemplar.libro', 'socio']);

        return view('prestamos.devolucion-confirmar', compact('prestamo'));
    }

    /**
     * RN-12 (sin identificar socio), RN-18 (restricción automática por atraso, salvo RN-07
     * Honorario o excepción de exención vigente), y la alerta de reserva pendiente (criterio de
     * aceptación explícito de este módulo — la gestión completa de la cola de reservas es Módulo 5).
     */
    public function devolver(Request $request, PrestamoDomiciliario $prestamo)
    {
        abort_unless(in_array($prestamo->estado, PrestamoDomiciliario::ESTADOS_ABIERTOS, true), 404);

        $datos = $request->validate([
            'fecha_devolucion_efectiva' => ['required', 'date'],
            'condicion_fisica' => ['nullable', 'string'],
        ]);

        $fechaDevolucion = Carbon::parse($datos['fecha_devolucion_efectiva']);
        $diasAtraso = $fechaDevolucion->greaterThan($prestamo->fecha_vencimiento)
            ? (int) $prestamo->fecha_vencimiento->diffInDays($fechaDevolucion)
            : 0;

        $mensajesAlerta = [];

        DB::transaction(function () use ($prestamo, $fechaDevolucion, $datos, $diasAtraso, &$mensajesAlerta) {
            $prestamo->update([
                'fecha_devolucion_efectiva' => $fechaDevolucion->toDateString(),
                'estado' => PrestamoDomiciliario::ESTADO_DEVUELTO,
            ]);

            if (! blank($datos['condicion_fisica'] ?? null)) {
                $prestamo->ejemplar->update(['condicion_fisica' => $datos['condicion_fisica']]);
            }

            if ($diasAtraso > 0) {
                $socio = $prestamo->socio;
                $tieneExcepcionDeExencion = $this->tieneExcepcionVigente($socio, ExcepcionAutorizada::TIPO_EXENCION_RESTRICCION);

                // RN-07: Honorario (sujeto_a_restriccion_automatica = false) no recibe restricción,
                // pero el atraso se registra igual en el historial (mismo criterio de Módulo 3).
                $generaRestriccion = $socio->tipoSocio->sujeto_a_restriccion_automatica && ! $tieneExcepcionDeExencion;

                HistorialAtraso::create([
                    'socio_id' => $socio->id,
                    'prestamo_domiciliario_id' => $prestamo->id,
                    'dias_atraso' => $diasAtraso,
                    'fecha_devolucion_efectiva' => $fechaDevolucion->toDateString(),
                    'restriccion_generada' => $generaRestriccion,
                ]);

                if ($generaRestriccion) {
                    // RN-18: 1 día de restricción por día de atraso, con tope máximo configurable.
                    $topeMaximo = (int) ParametroConfiguracion::obtener(ParametroConfiguracion::TOPE_MAXIMO_RESTRICCION_DIAS, 30);
                    $diasRestriccion = min($diasAtraso, $topeMaximo);

                    RestriccionSocio::create([
                        'socio_id' => $socio->id,
                        'tipo' => 'automatica',
                        'fecha_inicio' => $fechaDevolucion->toDateString(),
                        'fecha_fin' => $fechaDevolucion->copy()->addDays($diasRestriccion)->toDateString(),
                        'dias_atraso_origen' => $diasAtraso,
                        'prestamo_domiciliario_id' => $prestamo->id,
                    ]);
                }
            }

            // Criterio de aceptación explícito: "la devolución de un libro con reserva pendiente
            // activa la alerta de 'avisar al socio' ... dentro del ciclo de la misma request." Se
            // marca la reserva más antigua en estado pendiente; la gestión completa de la cola
            // (retiro, vencimiento de la ventana, cancelación) es Módulo 5.
            $reservaPendiente = Reserva::where('libro_id', $prestamo->ejemplar->libro_id)
                ->where('estado', 'pendiente')
                ->oldest('fecha_reserva')
                ->first();

            if ($reservaPendiente) {
                $ventanaHoras = (int) ParametroConfiguracion::obtener(ParametroConfiguracion::VENTANA_RETIRO_RESERVA_HORAS, 48);

                $reservaPendiente->update([
                    'estado' => 'personal_alertado',
                    'fecha_alerta_al_personal' => now(),
                    'fecha_limite_retiro' => now()->addHours($ventanaHoras),
                    'ejemplar_asignado_id' => $prestamo->ejemplar_id,
                ]);

                $mensajesAlerta[] = "Hay una reserva pendiente de {$reservaPendiente->socio->nombre_principal} para este libro — avisarle que ya está disponible.";
            }
        });

        return redirect()->route('prestamos.devolucion.buscar')
            ->with('status', 'Devolución registrada correctamente.')
            ->with('alertas', $mensajesAlerta);
    }

    private function tieneExcepcionVigente(Socio $socio, string $tipo): bool
    {
        return ExcepcionAutorizada::query()
            ->where('entidad_afectada_type', Socio::class)
            ->where('entidad_afectada_id', $socio->id)
            ->where('tipo', $tipo)
            ->get()
            ->contains(fn (ExcepcionAutorizada $excepcion) => $excepcion->estaVigente());
    }
}
