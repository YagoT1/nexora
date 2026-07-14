<?php

// Origen: Plan de Implementación v2, Módulo 3 — Socios, "CRUD de Socio", "Búsqueda de socios",
// "Vista de socio desde el mostrador", "Historial de préstamos del socio". Ver
// Fase 6 - Development/BRIEFING-MODULO-3-SOCIOS.md para el detalle de riesgos y decisiones.

namespace App\Http\Controllers\Socios;

use App\Http\Controllers\Controller;
use App\Models\Socio;
use App\Models\TipoSocio;
use Illuminate\Http\Request;

class SocioController extends Controller
{
    /**
     * Origen: Paso 4 del briefing. Búsqueda tolerante a mayúsculas/minúsculas y acentos (R-1:
     * ILIKE por sí solo no resuelve acentos, ver migración que habilita la extensión `unaccent`).
     * Se busca simultáneamente sobre nombre_principal y cada elemento de nombres_alternativos
     * (columna jsonb, R-3), sin reimplementar la comparación de acentos en PHP para no divergir
     * del comportamiento de la base de datos.
     */
    public function index(Request $request)
    {
        $busqueda = $request->string('busqueda')->trim()->toString();

        $socios = Socio::with('tipoSocio')
            ->when($busqueda !== '', function ($query) use ($busqueda) {
                $query->where(function ($q) use ($busqueda) {
                    $q->whereRaw('unaccent(nombre_principal) ILIKE unaccent(?)', ["%{$busqueda}%"])
                        ->orWhereRaw(
                            "EXISTS (
                                SELECT 1 FROM jsonb_array_elements_text(COALESCE(nombres_alternativos, '[]'::jsonb)) AS alt
                                WHERE unaccent(alt) ILIKE unaccent(?)
                            )",
                            ["%{$busqueda}%"]
                        );
                });
            })
            ->orderBy('nombre_principal')
            ->paginate(20)
            ->withQueryString();

        return view('socios.socios.index', compact('socios', 'busqueda'));
    }

    public function create()
    {
        $tiposSocio = TipoSocio::orderBy('nombre')->get();

        return view('socios.socios.create', compact('tiposSocio'));
    }

    public function store(Request $request)
    {
        $datos = $this->validarDatos($request);

        Socio::create($datos);

        return redirect()->route('socios.socios.index')->with('status', 'Socio creado correctamente.');
    }

    /**
     * Origen: Paso 5 del briefing — vista de mostrador. Reutiliza los métodos de dominio ya
     * existentes de Módulo 1 (RestriccionSocio::estaActiva()) en vez de reimplementar el cálculo
     * de vigencia. "Atrasos en los últimos 12 meses" se filtra por fecha_devolucion_efectiva (la
     * fecha real del atraso), no por created_at, para que sea correcto aunque el registro se
     * cargue o corrija después.
     */
    public function show(Socio $socio)
    {
        $socio->load('tipoSocio');

        $prestamosActivos = $socio->prestamosDomiciliarios()
            ->whereIn('estado', ['activo', 'atrasado'])
            ->with('ejemplar.libro')
            ->get();

        $reservasActivas = $socio->reservas()
            ->whereIn('estado', ['pendiente', 'personal_alertado'])
            ->with('libro')
            ->get();

        $restriccionVigente = $socio->restricciones()
            ->get()
            ->first(fn ($restriccion) => $restriccion->estaActiva());

        $atrasosUltimos12Meses = $socio->historialAtrasos()
            ->where('fecha_devolucion_efectiva', '>=', now()->subMonths(12)->toDateString())
            ->count();

        $historialPrestamos = $socio->prestamosDomiciliarios()
            ->with('ejemplar.libro')
            ->orderByDesc('fecha_prestamo')
            ->paginate(15, ['*'], 'historial');

        return view('socios.socios.show', compact(
            'socio',
            'prestamosActivos',
            'reservasActivas',
            'restriccionVigente',
            'atrasosUltimos12Meses',
            'historialPrestamos',
        ));
    }

    public function edit(Socio $socio)
    {
        $tiposSocio = TipoSocio::orderBy('nombre')->get();

        return view('socios.socios.edit', compact('socio', 'tiposSocio'));
    }

    public function update(Request $request, Socio $socio)
    {
        $datos = $this->validarDatos($request);

        $socio->update($datos);

        return redirect()->route('socios.socios.show', $socio)->with('status', 'Socio actualizado correctamente.');
    }

    /**
     * Origen: Modelo de Dominio v2, 2.2. `nombres_alternativos` llega del formulario como texto
     * separado por líneas (una entrada por línea, ver vista create/edit) y se normaliza acá a un
     * array antes de guardarlo en la columna jsonb — evita depender de que el navegador envíe un
     * array de inputs dinámicos para un caso de uso tan simple (YAGNI frente a Alpine.js con
     * inputs repetibles, que Categoria/Ejemplar no necesitaron y este campo tampoco requiere).
     */
    private function validarDatos(Request $request): array
    {
        $datos = $request->validate([
            'nombre_principal' => ['required', 'string', 'max:255'],
            'nombres_alternativos' => ['nullable', 'string'],
            'dni' => ['nullable', 'string', 'max:255'],
            'email' => ['nullable', 'email', 'max:255'],
            'telefono' => ['nullable', 'string', 'max:255'],
            'fecha_alta' => ['required', 'date'],
            'estado' => ['required', 'in:activo,inactivo'],
            'tipo_socio_id' => ['required', 'exists:tipos_socio,id'],
        ]);

        $datos['nombres_alternativos'] = collect(explode("\n", (string) ($datos['nombres_alternativos'] ?? '')))
            ->map(fn ($nombre) => trim($nombre))
            ->filter()
            ->values()
            ->all();

        return $datos;
    }
}
