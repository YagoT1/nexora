{{-- Sub-navegación entre las secciones del Catálogo. Se incluye en cada index.blade.php de
     catalogo.*. Cuando exista una vista de detalle de Libro (Paso 6) con búsqueda (Paso 5), esta
     barra puede simplificarse a favor de esa vista como punto de entrada principal. --}}
<nav class="mb-6 flex gap-4 text-sm border-b border-gray-200 pb-3">
    <a href="{{ route('catalogo.libros.index') }}" class="{{ request()->routeIs('catalogo.libros.*') ? 'font-semibold' : 'text-gray-500' }}">Libros</a>
    <a href="{{ route('catalogo.autores.index') }}" class="{{ request()->routeIs('catalogo.autores.*') ? 'font-semibold' : 'text-gray-500' }}">Autores</a>
    <a href="{{ route('catalogo.editoriales.index') }}" class="{{ request()->routeIs('catalogo.editoriales.*') ? 'font-semibold' : 'text-gray-500' }}">Editoriales</a>
    <a href="{{ route('catalogo.categorias.index') }}" class="{{ request()->routeIs('catalogo.categorias.*') ? 'font-semibold' : 'text-gray-500' }}">Categorías</a>
</nav>
