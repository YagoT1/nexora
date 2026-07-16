<?php

// Origen: Plan de Implementación v2, Módulo 6 — Excepciones y restricciones. Ver
// Fase 6 - Development/BRIEFING-MODULO-6-EXCEPCIONES-RESTRICCIONES.md, Paso 3. RN-10: CRUD
// restringido a Administrador (a diferencia de Catálogo/Socios/Préstamos, que permiten Personal) —
// ver ruta en routes/web.php, grupo 'excepciones.*' con role:administrador únicamente.

namespace App\Http\Controllers\Excepciones;

use App\Http\Controllers\Controller;
use App\Models\Ejemplar;
use App\Models\ExcepcionAutorizada;
use App\Models\Socio;
use Illuminate\Http\Request;

class ExcepcionController extends Controller
{
    /**
     * Criterio de aceptación: "Pantalla de listado de excepciones vigentes, con filtros por tipo y
     * entidad." El estado mostrado es el derivado (ExcepcionAutorizada::estadoVisible(), Decisión
     * D-15), no la columna cruda — para que una excepción vencida aparezca como tal sin depender de
     * ninguna tarea programada.
     *
     * Origen: Módulo 6, Paso 5. `entidad_afectada_id` es un filtro adicional al de tipo/clase de
     * entidad ya existente (Paso 3) — permite enlazar directamente desde socios.socios.show y
     * catalogo.libros.show ("excepciones sobre este Socio/Ejemplar puntual"), sin construir una
     * pantalla de navegación dedicada nueva (el plan solo pide reutilizar el CRUD existente).
     */
    public function index(Request $request)
    {
        $tipoFiltro = $request->string('tipo')->toString();
        $entidadFiltro = $request->string('entidad_afectada_type')->toString();
        $entidadIdFiltro = $request->string('entidad_afectada_id')->toString();

        $excepciones = ExcepcionAutorizada::query()
            ->with(['entidadAfectada', 'autorizadoPor', 'revocadoPor'])
            ->when($tipoFiltro !== '', fn ($q) => $q->where('tipo', $tipoFiltro))
            ->when($entidadFiltro !== '', fn ($q) => $q->where('entidad_afectada_type', $entidadFiltro))
            ->when($entidadIdFiltro !== '', fn ($q) => $q->where('entidad_afectada_id', $entidadIdFiltro))
            ->latest('fecha_autorizacion')
            ->paginate(20)
            ->withQueryString();

        return view('excepciones.index', compact('excepciones', 'tipoFiltro', 'entidadFiltro', 'entidadIdFiltro'));
    }

    /**
     * Origen: CU-1 del briefing. El tipo elegido determina qué clase de entidad se busca (Socio o
     * Ejemplar) — ver ExcepcionAutorizada::ENTIDADES_POR_TIPO, única fuente de verdad de ese mapeo
     * (D-03: un único mecanismo de excepción, no una entidad separada por tipo).
     */
    public function create(Request $request)
    {
        $tipo = $request->string('tipo')->toString();
        $entidadEsperada = $tipo !== '' && isset(ExcepcionAutorizada::ENTIDADES_POR_TIPO[$tipo])
            ? ExcepcionAutorizada::ENTIDADES_POR_TIPO[$tipo]
            : null;

        $socio = $request->filled('socio_id')
            ? Socio::findOrFail($request->integer('socio_id'))
            : null;
        $ejemplar = $request->filled('ejemplar_id')
            ? Ejemplar::with('libro')->findOrFail($request->integer('ejemplar_id'))
            : null;

        $busquedaSocio = $request->string('busqueda_socio')->trim()->toString();
        $sociosEncontrados = collect();
        if ($entidadEsperada === Socio::class && ! $socio && $busquedaSocio !== '') {
            $sociosEncontrados = Socio::buscar($busquedaSocio)->orderBy('nombre_principal')->limit(10)->get();
        }

        $busquedaLibro = $request->string('busqueda_libro')->trim()->toString();
        $ejemplaresEncontrados = collect();
        if ($entidadEsperada === Ejemplar::class && ! $ejemplar && $busquedaLibro !== '') {
            $ejemplaresEncontrados = Ejemplar::with('libro')
                ->whereHas('libro', fn ($q) => $q->whereRaw('unaccent(titulo) ILIKE unaccent(?)', ["%{$busquedaLibro}%"]))
                ->get();
        }

        return view('excepciones.create', compact(
            'tipo', 'entidadEsperada', 'socio', 'ejemplar',
            'busquedaSocio', 'sociosEncontrados', 'busquedaLibro', 'ejemplaresEncontrados',
        ));
    }

    /**
     * RN-11: autorizado_por y fecha_autorizacion los fija el sistema, nunca el formulario — el
     * usuario autenticado y el momento del alta, sin posibilidad de sobrescritura.
     */
    public function store(Request $request)
    {
        $datos = $request->validate([
            'tipo' => ['required', 'in:'.implode(',', array_keys(ExcepcionAutorizada::ENTIDADES_POR_TIPO))],
            'entidad_id' => ['required', 'integer'],
            'motivo' => ['required', 'string'],
            'fecha_inicio' => ['required', 'date'],
            'fecha_fin' => ['nullable', 'date', 'after_or_equal:fecha_inicio'],
        ]);

        $entidadClase = ExcepcionAutorizada::ENTIDADES_POR_TIPO[$datos['tipo']];
        $entidad = $entidadClase::findOrFail($datos['entidad_id']);

        ExcepcionAutorizada::create([
            'tipo' => $datos['tipo'],
            'entidad_afectada_type' => $entidadClase,
            'entidad_afectada_id' => $entidad->id,
            'autorizado_por' => auth()->id(),
            'fecha_autorizacion' => now(),
            'motivo' => $datos['motivo'],
            'fecha_inicio' => $datos['fecha_inicio'],
            'fecha_fin' => $datos['fecha_fin'] ?? null,
            'estado' => ExcepcionAutorizada::ESTADO_VIGENTE,
        ]);

        return redirect()->route('excepciones.index')->with('status', 'Excepción autorizada creada correctamente.');
    }

    /**
     * Criterio de aceptación: "El Administrador puede revocar una excepción antes de su fecha de
     * fin. La revocación queda registrada con fecha y usuario." No se permite revocar dos veces.
     */
    public function revocar(ExcepcionAutorizada $excepcion)
    {
        abort_if($excepcion->estado === ExcepcionAutorizada::ESTADO_REVOCADA, 404);

        $excepcion->update([
            'estado' => ExcepcionAutorizada::ESTADO_REVOCADA,
            'revocado_por' => auth()->id(),
            'fecha_revocacion' => now(),
        ]);

        return back()->with('status', 'Excepción revocada correctamente.');
    }
}
