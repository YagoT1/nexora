<?php

// Origen: Plan de Implementación v2, Módulo 6 — Excepciones y restricciones. Ver
// Fase 6 - Development/BRIEFING-MODULO-6-EXCEPCIONES-RESTRICCIONES.md, Paso 4 (CU-3). A diferencia
// del CRUD de ExcepcionAutorizada (RN-10: solo Administrador), este controlador acepta también
// Personal — ver ruta en routes/web.php, grupo 'restricciones.*' con role:administrador,personal
// (Riesgo R-4: dos middlewares de rol distintos dentro del mismo módulo).
//
// Alcance explícitamente excluido por el briefing: revocación anticipada de una RestriccionSocio —
// el plan solo describe alta manual con fecha de fin definida, no se inventa esa pantalla.

namespace App\Http\Controllers\Restricciones;

use App\Http\Controllers\Controller;
use App\Models\RestriccionSocio;
use App\Models\Socio;
use Illuminate\Http\Request;

class RestriccionController extends Controller
{
    /**
     * Criterio del plan: "listado de restricciones activas/históricas por socio". Una única lista
     * ordenada por fecha de inicio descendente — estaActiva() (ya existente desde el Módulo 1)
     * distingue en la vista cuáles están vigentes de las históricas, sin necesitar dos consultas.
     */
    public function index(Socio $socio)
    {
        $restricciones = $socio->restricciones()
            ->with('generadaPor')
            ->orderByDesc('fecha_inicio')
            ->get();

        return view('restricciones.index', compact('socio', 'restricciones'));
    }

    /**
     * CU-3: el Personal o Administrador completa socio (ya resuelto por la ruta anidada), motivo
     * (`observaciones`) y fecha de fin; el sistema fija `tipo = manual`, `fecha_inicio = hoy` y
     * `generada_por_usuario_id` al usuario autenticado — nunca editable desde el formulario.
     *
     * Salvaguarda de integridad (no una regla de negocio nueva, sino una verificación de
     * consistencia de datos): se evita crear una segunda restricción activa simultánea sobre el
     * mismo socio, que dejaría dos registros compitiendo por representar "la" restricción vigente
     * en `PrestamoController`/`Socio::restricciones()->first(fn ($r) => $r->estaActiva())`.
     */
    public function store(Request $request, Socio $socio)
    {
        $datos = $request->validate([
            'observaciones' => ['required', 'string'],
            'fecha_fin' => ['required', 'date', 'after_or_equal:today'],
        ]);

        $restriccionActiva = $socio->restricciones()->get()->first(fn (RestriccionSocio $r) => $r->estaActiva());
        if ($restriccionActiva) {
            return back()->withInput()->withErrors([
                'fecha_fin' => "El socio ya tiene una restricción activa hasta el {$restriccionActiva->fecha_fin->format('d/m/Y')}.",
            ]);
        }

        RestriccionSocio::create([
            'socio_id' => $socio->id,
            'tipo' => RestriccionSocio::TIPO_MANUAL,
            'fecha_inicio' => now(),
            'fecha_fin' => $datos['fecha_fin'],
            'generada_por_usuario_id' => auth()->id(),
            'observaciones' => $datos['observaciones'],
        ]);

        return redirect()->route('restricciones.index', $socio)
            ->with('status', 'Restricción registrada correctamente.');
    }
}
