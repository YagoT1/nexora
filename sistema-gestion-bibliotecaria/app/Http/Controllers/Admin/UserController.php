<?php

// Origen: Plan de Implementación v2, Módulo 1 — "Panel de administración básico: gestión de
// usuarios (crear, editar, inactivar, asignar rol)". Ruta protegida por middleware role:administrador.

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\User;
use Illuminate\Http\Request;
use Illuminate\Validation\Rules\Password;

class UserController extends Controller
{
    public function index()
    {
        $usuarios = User::orderBy('name')->paginate(20);

        return view('admin.users.index', compact('usuarios'));
    }

    public function create()
    {
        return view('admin.users.create', ['roles' => User::ROLES]);
    }

    public function store(Request $request)
    {
        $datos = $request->validate([
            'name' => ['required', 'string', 'max:255'],
            'email' => ['required', 'string', 'email', 'max:255', 'unique:users,email'],
            'password' => ['required', 'confirmed', Password::defaults()],
            'rol' => ['required', 'in:' . implode(',', User::ROLES)],
        ]);

        $datos['password'] = bcrypt($datos['password']);
        User::create($datos);

        return redirect()->route('admin.users.index')->with('status', 'Usuario creado correctamente.');
    }

    public function edit(User $user)
    {
        return view('admin.users.edit', ['usuario' => $user, 'roles' => User::ROLES]);
    }

    public function update(Request $request, User $user)
    {
        $datos = $request->validate([
            'name' => ['required', 'string', 'max:255'],
            'email' => ['required', 'string', 'email', 'max:255', 'unique:users,email,' . $user->id],
            'rol' => ['required', 'in:' . implode(',', User::ROLES)],
            'estado' => ['required', 'in:activo,inactivo'],
        ]);

        // Salvaguarda agregada tras revisión de código (no deriva de RN/DA existente, ver
        // eos-benchmark/Fase 6 - Development/ADR-003-...): un administrador no puede cambiar su
        // propio rol ni su propio estado desde este panel. Sin esta restricción, un administrador
        // podía autodesactivarse o quitarse el rol de administrador y, si era el único activo,
        // dejar el sistema entero sin ningún usuario con permisos de administración.
        if ($user->is($request->user()) && ($datos['rol'] !== $user->rol || $datos['estado'] !== $user->estado)) {
            return back()
                ->withInput()
                ->withErrors(['rol' => 'No podés modificar tu propio rol o estado desde este panel.']);
        }

        $user->update($datos);

        return redirect()->route('admin.users.index')->with('status', 'Usuario actualizado correctamente.');
    }

    /**
     * "Inactivar" en lugar de eliminar: preserva la trazabilidad de operaciones ya registradas
     * por este usuario (préstamos, excepciones, auditoría), consistente con el criterio del
     * dominio de no eliminar registros históricos.
     */
    public function inactivar(Request $request, User $user)
    {
        // Misma salvaguarda que en update(): ver comentario ahí. Un administrador no puede
        // inactivar su propia cuenta desde este panel.
        if ($user->is($request->user())) {
            return redirect()->route('admin.users.index')->with('status', 'No podés inactivar tu propia cuenta.');
        }

        $user->update(['estado' => 'inactivo']);

        return redirect()->route('admin.users.index')->with('status', 'Usuario inactivado correctamente.');
    }

    public function reactivar(User $user)
    {
        $user->update(['estado' => 'activo']);

        return redirect()->route('admin.users.index')->with('status', 'Usuario reactivado correctamente.');
    }
}
