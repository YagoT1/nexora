{{-- Sub-navegación entre las secciones de Socios. Mismo patrón que catalogo._subnav (Módulo 2). --}}
<nav class="mb-6 flex gap-4 text-sm border-b border-gray-200 pb-3">
    <a href="{{ route('socios.socios.index') }}" class="{{ request()->routeIs('socios.socios.*') ? 'font-semibold' : 'text-gray-500' }}">Socios</a>
    <a href="{{ route('socios.tipos-socio.index') }}" class="{{ request()->routeIs('socios.tipos-socio.*') ? 'font-semibold' : 'text-gray-500' }}">Tipos de socio</a>
</nav>
