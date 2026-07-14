{{-- Partial compartido entre create.blade.php y edit.blade.php: mismo formulario, misma lista de
     autores/editoriales/categorías disponibles ($autoresDisponibles, $editoriales,
     $categoriasDisponibles), y opcionalmente $libro cuando se edita. --}}
@php
    $autoresSeleccionados = old('autores', isset($libro) ? $libro->autores->pluck('id')->all() : []);
    $categoriasSeleccionadas = old('categorias', isset($libro) ? $libro->categorias->pluck('id')->all() : []);
@endphp

<div>
    <label class="block text-sm mb-1">Título</label>
    <input type="text" name="titulo" value="{{ old('titulo', $libro->titulo ?? '') }}" class="w-full border-gray-300 rounded" required>
    @error('titulo') <p class="text-red-600 text-xs mt-1">{{ $message }}</p> @enderror
</div>

<div class="grid grid-cols-2 gap-4">
    <div>
        <label class="block text-sm mb-1">ISBN (opcional)</label>
        <input type="text" name="isbn" value="{{ old('isbn', $libro->isbn ?? '') }}" class="w-full border-gray-300 rounded">
        @error('isbn') <p class="text-red-600 text-xs mt-1">{{ $message }}</p> @enderror
    </div>
    <div>
        <label class="block text-sm mb-1">Año de publicación (opcional)</label>
        <input type="number" name="anio_publicacion" value="{{ old('anio_publicacion', $libro->anio_publicacion ?? '') }}" class="w-full border-gray-300 rounded">
        @error('anio_publicacion') <p class="text-red-600 text-xs mt-1">{{ $message }}</p> @enderror
    </div>
</div>

<div class="grid grid-cols-2 gap-4">
    <div>
        <label class="block text-sm mb-1">Edición (opcional)</label>
        <input type="text" name="edicion" value="{{ old('edicion', $libro->edicion ?? '') }}" class="w-full border-gray-300 rounded">
        @error('edicion') <p class="text-red-600 text-xs mt-1">{{ $message }}</p> @enderror
    </div>
    <div>
        <label class="block text-sm mb-1">Idioma (opcional)</label>
        <input type="text" name="idioma" value="{{ old('idioma', $libro->idioma ?? '') }}" class="w-full border-gray-300 rounded">
        @error('idioma') <p class="text-red-600 text-xs mt-1">{{ $message }}</p> @enderror
    </div>
</div>

<div>
    <label class="block text-sm mb-1">Descripción (opcional)</label>
    <textarea name="descripcion" class="w-full border-gray-300 rounded" rows="3">{{ old('descripcion', $libro->descripcion ?? '') }}</textarea>
    @error('descripcion') <p class="text-red-600 text-xs mt-1">{{ $message }}</p> @enderror
</div>

<div>
    <label class="block text-sm mb-1">Editorial (opcional)</label>
    <select name="editorial_id" class="w-full border-gray-300 rounded">
        <option value="">— Sin editorial —</option>
        @foreach ($editoriales as $editorial)
            <option value="{{ $editorial->id }}" {{ (string) old('editorial_id', $libro->editorial_id ?? '') === (string) $editorial->id ? 'selected' : '' }}>
                {{ $editorial->nombre }}
            </option>
        @endforeach
    </select>
    @error('editorial_id') <p class="text-red-600 text-xs mt-1">{{ $message }}</p> @enderror
</div>

<div>
    <label class="block text-sm mb-1">
        Autores (opcional — un libro puede no tener autor identificable: recopilaciones, obras anónimas)
    </label>
    @if ($autoresDisponibles->isEmpty())
        <p class="text-xs text-gray-500">No hay autores cargados todavía.</p>
    @else
        <div class="border border-gray-200 rounded p-3 max-h-40 overflow-y-auto space-y-1">
            @foreach ($autoresDisponibles as $autor)
                <label class="flex items-center gap-2 text-sm">
                    <input type="checkbox" name="autores[]" value="{{ $autor->id }}"
                           {{ in_array($autor->id, $autoresSeleccionados) ? 'checked' : '' }}>
                    {{ $autor->nombre }}
                </label>
            @endforeach
        </div>
    @endif
    @error('autores') <p class="text-red-600 text-xs mt-1">{{ $message }}</p> @enderror
</div>

<div>
    <label class="block text-sm mb-1">Categorías (opcional)</label>
    @if ($categoriasDisponibles->isEmpty())
        <p class="text-xs text-gray-500">No hay categorías cargadas todavía.</p>
    @else
        <div class="border border-gray-200 rounded p-3 max-h-56 overflow-y-auto space-y-1">
            @foreach ($categoriasDisponibles as $categoria)
                <label class="flex items-center gap-2 text-sm">
                    <input type="checkbox" name="categorias[]" value="{{ $categoria->id }}"
                           {{ in_array($categoria->id, $categoriasSeleccionadas) ? 'checked' : '' }}>
                    {{ $categoria->nombre }}
                </label>
                @foreach ($categoria->subcategorias as $subcategoria)
                    <label class="flex items-center gap-2 text-sm pl-6">
                        <input type="checkbox" name="categorias[]" value="{{ $subcategoria->id }}"
                               {{ in_array($subcategoria->id, $categoriasSeleccionadas) ? 'checked' : '' }}>
                        — {{ $subcategoria->nombre }}
                    </label>
                @endforeach
            @endforeach
        </div>
    @endif
    @error('categorias') <p class="text-red-600 text-xs mt-1">{{ $message }}</p> @enderror
</div>
