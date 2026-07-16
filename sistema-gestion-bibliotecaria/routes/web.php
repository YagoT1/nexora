<?php

use App\Http\Controllers\Admin\UserController;
use App\Http\Controllers\Catalogo\AutorController;
use App\Http\Controllers\Catalogo\CategoriaController;
use App\Http\Controllers\Catalogo\EditorialController;
use App\Http\Controllers\Catalogo\EjemplarController;
use App\Http\Controllers\Catalogo\LibroController;
use App\Http\Controllers\Excepciones\ExcepcionController;
use App\Http\Controllers\Prestamos\PrestamoController;
use App\Http\Controllers\Prestamos\ReservaController;
use App\Http\Controllers\ProfileController;
use App\Http\Controllers\Socios\SocioController;
use App\Http\Controllers\Socios\TipoSocioController;
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
// en inglés) no está garantizado a producir la forma singular correcta para sustantivos en
// español (p.ej. "autores" podría resolverse a "autore" en vez de "autor"), y esto no puede
// verificarse en este entorno por no contar con PHP/Composer reales (ver ADR-002). Fijar el
// nombre explícitamente elimina esa ambigüedad sin depender de esa inferencia.
Route::middleware(['auth', 'role:administrador,personal'])
    ->prefix('catalogo')
    ->name('catalogo.')
    ->group(function () {
        Route::resource('autores', AutorController::class, ['except' => ['show'], 'parameters' => ['autores' => 'autor']]);
        Route::resource('editoriales', EditorialController::class, ['except' => ['show'], 'parameters' => ['editoriales' => 'editorial']]);
        Route::resource('categorias', CategoriaController::class, ['except' => ['show'], 'parameters' => ['categorias' => 'categoria']]);
        // 'show' habilitado (Paso 6): vista de detalle de Libro con sus ejemplares y estado actual.
        Route::resource('libros', LibroController::class, ['parameters' => ['libros' => 'libro']]);
        // Anidada bajo Libro: un Ejemplar siempre existe en el contexto de un Libro (D-02). Sin
        // index/show propios: el listado es responsabilidad de la vista de detalle de Libro (Paso 6).
        Route::resource('libros.ejemplares', EjemplarController::class, [
            'except' => ['index', 'show'],
            'parameters' => ['libros' => 'libro', 'ejemplares' => 'ejemplar'],
        ]);
        // Módulo 5 (Reservas) — ver BRIEFING-MODULO-5-RENOVACIONES-RESERVAS.md, Paso 4. Nace desde
        // la vista de detalle de Libro (Módulo 2), aunque el controlador vive en el namespace
        // Prestamos junto con PrestamoController (mismo dominio de circulación).
        Route::resource('libros.reservas', ReservaController::class, [
            'only' => ['create', 'store'],
            'parameters' => ['libros' => 'libro', 'reservas' => 'reserva'],
        ]);
    });
// --- fin Módulo 2 (paso 1: Autor, Editorial; paso 2: Categoría; paso 3: Libro; paso 4: Ejemplar;
// paso 5: búsqueda vía query string en 'libros.index'; paso 6: 'libros.show') ---

// --- Módulo 3 (Socios) — en construcción, ver Fase 6 - Development/BRIEFING-MODULO-3-SOCIOS.md ---
// Acceso: Administrador y Personal (Modelo de Dominio v2, 6.1: Voluntario no gestiona socios,
// mismo criterio que Catálogo).
Route::middleware(['auth', 'role:administrador,personal'])
    ->prefix('socios')
    ->name('socios.')
    ->group(function () {
        Route::resource('tipos-socio', TipoSocioController::class, ['parameters' => ['tipos-socio' => 'tipoSocio']]);
        // 'show' es la vista de mostrador (Paso 5): préstamos activos, reservas activas,
        // restricción vigente, atrasos en los últimos 12 meses e historial paginado (Paso 6). No
        // hay 'destroy': el padrón de socios no se elimina (RN-14/DA-05, dato auditado), se
        // inactiva editando el campo 'estado'.
        Route::resource('socios', SocioController::class, ['except' => ['destroy'], 'parameters' => ['socios' => 'socio']]);
    });
// --- fin Módulo 3 (paso 1: Tipo de Socio) ---

// --- Módulo 4 (Préstamos y devoluciones) — en construcción, ver
// Fase 6 - Development/BRIEFING-MODULO-4-PRESTAMOS.md ---
// Acceso: Administrador y Personal (mismo criterio que Catálogo y Socios).
Route::middleware(['auth', 'role:administrador,personal'])
    ->prefix('prestamos')
    ->name('prestamos.')
    ->group(function () {
        Route::get('nuevo', [PrestamoController::class, 'create'])->name('create');
        Route::post('/', [PrestamoController::class, 'store'])->name('store');
        Route::get('devolucion', [PrestamoController::class, 'buscarDevolucion'])->name('devolucion.buscar');
        Route::get('{prestamo}/devolucion', [PrestamoController::class, 'confirmarDevolucion'])->name('devolucion.confirmar');
        Route::post('{prestamo}/devolucion', [PrestamoController::class, 'devolver'])->name('devolucion.store');
        // --- Módulo 5 (Renovaciones) — ver
        // Fase 6 - Development/BRIEFING-MODULO-5-RENOVACIONES-RESERVAS.md, Paso 3. Vive en el mismo
        // grupo de rutas 'prestamos.*' porque opera sobre PrestamoDomiciliario, mismo controlador.
        Route::post('{prestamo}/renovar', [PrestamoController::class, 'renovar'])->name('renovar');
    });
// --- fin Módulo 4 (paso 2: registro de préstamo; paso 3: devolución) ---
// --- Módulo 5 (paso 3: renovación, dentro del grupo 'prestamos.*'; paso 4: reservas, dentro del
// grupo 'catalogo.*' de arriba) ---

// --- Módulo 6 (Excepciones y restricciones) — en construcción, ver
// Fase 6 - Development/BRIEFING-MODULO-6-EXCEPCIONES-RESTRICCIONES.md ---
// Acceso: solo Administrador (RN-10: el CRUD de ExcepcionAutorizada — creación, modificación,
// revocación — está reservado al Administrador; a diferencia de Catálogo/Socios/Préstamos, que
// también permiten Personal). Único otro grupo con este mismo nivel de restricción es 'admin.*'
// (Módulo 1).
Route::middleware(['auth', 'role:administrador'])
    ->prefix('excepciones')
    ->name('excepciones.')
    ->group(function () {
        Route::get('/', [ExcepcionController::class, 'index'])->name('index');
        Route::get('nueva', [ExcepcionController::class, 'create'])->name('create');
        Route::post('/', [ExcepcionController::class, 'store'])->name('store');
        Route::patch('{excepcion}/revocar', [ExcepcionController::class, 'revocar'])->name('revocar');
    });
// --- fin Módulo 6 (paso 3: CRUD de Excepciones Autorizadas) ---
