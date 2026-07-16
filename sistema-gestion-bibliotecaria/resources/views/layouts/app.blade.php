{{-- Origen: Plan de Implementación v2, Módulo 1 — "Layout base de la aplicación (navegación,
     estructura visual, responsive)". Blade + Alpine.js (DA-03 v2: sin Inertia/Vue en Fase 1). --}}
<!DOCTYPE html>
<html lang="es" x-data="{ menuAbierto: false }">
<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <title>{{ config('app.name', 'Sistema de Gestión Bibliotecaria') }} — @yield('titulo', 'Inicio')</title>
    @vite(['resources/css/app.css', 'resources/js/app.js'])
</head>
<body class="min-h-screen bg-gray-50 text-gray-900">
<nav class="bg-white border-b border-gray-200">
    <div class="max-w-6xl mx-auto px-4 sm:px-6 lg:px-8">
        <div class="flex justify-between h-16 items-center">
            <a href="{{ route('dashboard') }}" class="font-semibold">Biblioteca</a>

            {{-- Alpine.js: menú responsive sin JavaScript de framework adicional --}}
            <button class="sm:hidden" @click="menuAbierto = !menuAbierto" aria-label="Abrir menú">
                ☰
            </button>

            <div class="hidden sm:flex sm:items-center sm:space-x-6">
                @auth
                    {{-- Modelo de Dominio v2, 6.1: "Gestionar catálogo y ejemplares" / "Gestionar
                         socios" -> Administrador y Personal. --}}
                    @if (auth()->user()->esAdministrador() || auth()->user()->esPersonal())
                        <a href="{{ route('catalogo.libros.index') }}" class="text-sm">Catálogo</a>
                        <a href="{{ route('socios.socios.index') }}" class="text-sm">Socios</a>
                        <a href="{{ route('prestamos.create') }}" class="text-sm">Nuevo préstamo</a>
                        <a href="{{ route('prestamos.devolucion.buscar') }}" class="text-sm">Devolución</a>
                    @endif
                    @if (auth()->user()->esAdministrador())
                        {{-- Origen: Módulo 6, Paso 5. RN-10: el CRUD de ExcepcionAutorizada es
                             exclusivo de Administrador — mismo criterio que "Administración". --}}
                        <a href="{{ route('excepciones.index') }}" class="text-sm">Excepciones</a>
                        <a href="{{ route('admin.users.index') }}" class="text-sm">Administración</a>
                    @endif
                    <span class="text-sm text-gray-500">{{ auth()->user()->name }} ({{ auth()->user()->rol }})</span>
                    <form method="POST" action="{{ route('logout') }}">
                        @csrf
                        <button type="submit" class="text-sm">Cerrar sesión</button>
                    </form>
                @endauth
            </div>
        </div>

        <div class="sm:hidden pb-4" x-show="menuAbierto" x-cloak>
            @auth
                @if (auth()->user()->esAdministrador() || auth()->user()->esPersonal())
                    <a href="{{ route('catalogo.libros.index') }}" class="block py-2 text-sm">Catálogo</a>
                    <a href="{{ route('socios.socios.index') }}" class="block py-2 text-sm">Socios</a>
                    <a href="{{ route('prestamos.create') }}" class="block py-2 text-sm">Nuevo préstamo</a>
                    <a href="{{ route('prestamos.devolucion.buscar') }}" class="block py-2 text-sm">Devolución</a>
                @endif
                @if (auth()->user()->esAdministrador())
                    <a href="{{ route('excepciones.index') }}" class="block py-2 text-sm">Excepciones</a>
                    <a href="{{ route('admin.users.index') }}" class="block py-2 text-sm">Administración</a>
                @endif
                <form method="POST" action="{{ route('logout') }}">
                    @csrf
                    <button type="submit" class="block py-2 text-sm">Cerrar sesión</button>
                </form>
            @endauth
        </div>
    </div>
</nav>

<main class="max-w-6xl mx-auto px-4 sm:px-6 lg:px-8 py-8">
    @if (session('status'))
        <div class="mb-4 rounded border border-green-300 bg-green-50 px-4 py-3 text-sm text-green-800">
            {{ session('status') }}
        </div>
    @endif

    @yield('contenido')
</main>
</body>
</html>
