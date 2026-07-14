<?php

use App\Http\Controllers\Admin\UserController;
use App\Http\Controllers\Catalogo\AutorController;
use App\Http\Controllers\Catalogo\CategoriaController;
use App\Http\Controllers\Catalogo\EditorialController;
use App\Http\Controllers\ProfileController;
use Illuminate\Support\Facades\Route;

Route::get('/', function () {
    return view('welcome');
});

Route::get('/dashboard', function () {
    return view('dashboard');
})->middleware(['auth', 'verified'])->name('dashboard');

Route::middleware('auth')->group(function () {
    Route::get('/profile', [ProfileController::class, 'edit'])->name('profile.edit');
    Route::patch('/profile', [ProfileController::class, 'update'])->name('profile.update');
    Route::delete('/profile', [ProfileController::class, 'destroy'])->name('profile.destroy');
});

require __DIR__.'/auth.php';

// --- Módulo 1 ---
// Origen: sistema-gestion-bibliotecaria/routes/web.php (ver docs/BOOTSTRAP.md paso 3).
Route::middleware(['auth', 'role:administrador'])
    ->prefix('admin')
    ->name('admin.')
    ->group(function () {
        Route::get('users', [UserController::class, 'index'])->name('users.index');
        Route::get('users/create', [UserController::class, 'create'])->name('users.create');
        Route::post('users', [UserController::class, 'store'])->name('users.store');
        Route::get('users/{user}/edit', [UserController::class, 'edit'])->name('users.edit');
        Route::put('users/{user}', [UserController::class, 'update'])->name('users.update');
        Route::patch('users/{user}/inactivar', [UserController::class, 'inactivar'])->name('users.inactivar');
        Route::patch('users/{user}/reactivar', [UserController::class, 'reactivar'])->name('users.reactivar');
    });
// --- fin Módulo 1 ---

// --- Módulo 2 (Catálogo) — en construcción, ver Fase 6 - Development/BRIEFING-MODULO-2-CATALOGO.md ---
// Acceso: Administrador y Personal (Modelo de Dominio v2, 6.1: Voluntario no gestiona catálogo).
//
// Nota técnica: se fija explícitamente el nombre del parámetro de cada resource route
// ('parameters' => [...]) en lugar de dejar que Laravel lo derive automáticamente de la URI en
// plural. El singularizador de Laravel (Illuminate\Support\Pluralizer, Doctrine Inflector, reglas
// en ingl