<?php

// Origen: Plan de Implementación v2, Módulo 3 — Socios, "CRUD de Tipo de Socio". D-04
// (configuración sin intervención técnica: límite y flag de restricción automática editables
// desde acá, sin tocar código). Ruta protegida por middleware role:administrador,personal
// (Modelo de Dominio v2, 6.1: "Gestionar socios" -> Administrador y Personal, no Voluntario;
// mismo criterio de acceso que Catálogo). Mismo patrón que AutorController/EditorialController
// de Módulo 2 (CRUD simple, sin jerarquía, validación inline).

namespace App\Http\Controllers\Socios;

use App\Http\Controllers\Controller;
use App\Models\TipoSocio;
use Illuminate\Http\Request;

class TipoSocioController extends Controller
{
    public function index()
    {
        $tiposSocio = TipoSocio::withCount('socios')->orderBy('nombre')->paginate(20);

        return view('socios.tipos-socio.index', compact('tiposSocio'));
    }

    public function create()
    {
        return view('socios.tipos-socio.create');
    }

    public function store(Request $request)
    {
        $datos = $request->validate([
            'nombre' => ['required', 'string', 'max:255', 'unique:tipos_socio,nombre'],
            'limite_prestamos_simultaneos' => ['required', 'integer', 'min:0', 'max:65535'],
            'sujeto_a_restriccion_automatica' => ['sometimes', 'boolean'],
        ]);
        $datos['sujeto_a_restriccion_automatica'] = $request->boolean('sujeto_a_restriccion_automatica');

        TipoSocio::create($datos);

        return redirect()->route('socios.tipos-socio.index')->with('status', 'Tipo de socio creado correctamente.');
    }

    public function edit(TipoSocio $tipoSocio)
    {
        return view('socios.tipos-socio.edit', compact('tipoSocio'));
    }

    /**
     * D-04: el límite y el flag de restricción automática se leen desde la base de datos en cada
     * validación (RestriccionSocio/Módulo 4 futuro), nunca se cachean en código ni en configuración
     * estática — por lo que un cambio acá se aplica de inmediato, sin reiniciar el sistema.
     */
    public function update(Request $request, TipoSocio $tipoSocio)
    {
        $datos = $request->validate([
            'nombre' => ['required', 'string', 'max:255', 'unique:tipos_socio,nombre,'.$tipoSocio->id],
            'limite_prestamos_simultaneos' => ['required', 'integer', 'min:0', 'max:65535'],
            'sujeto_a_restriccion_automatica' => ['sometimes', 'boolean'],
        ]);
        $datos['sujeto_a_restriccion_automatica'] = $request->boolean('sujeto_a_restriccion_automatica');

        $tipoSocio->update($datos);

        return redirect()->route('socios.tipos-socio.index')->with('status', 'Tipo de socio actualizado correctamente.');
    }

    /**
     * Igual criterio que Autor/Editorial (Módulo 2): no se permite eliminar un Tipo de Socio que
     * todavía tiene Socios asociados, para no dejar ese vínculo obligatorio (belongsTo not-nullable)
     * roto sin aviso.
     */
    public function destroy(TipoSocio $tipoSocio)
    {
        if ($tipoSocio->socios()->exists()) {
            return back()->with('status', 'No se puede eliminar: el tipo de socio tiene socios asociados.');
        }

        $tipoSocio->delete();

        return redirect()->route('socios.tipos-socio.index')->with('status', 'Tipo de socio eliminado correctamente.');
    }
}
