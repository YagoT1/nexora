{{-- Partial compartido entre create.blade.php y edit.blade.php de Ejemplar. $libro siempre
     presente; $ejemplar solo en edición. --}}
<div>
    <label class="block text-sm mb-1">Modalidad de acceso</label>
    <select name="modalidad_acceso" class="w-full border-gray-300 rounded" required>
        @foreach (\App\Models\Ejemplar::MODALIDADES_ACCESO as $modalidad)
            <option value="{{ $modalidad }}" {{ old('modalidad_acceso', $ejemplar->modalidad_acceso ?? \App\Models\Ejemplar::MODALIDAD_LIBRE_CIRCULACION) === $modalidad ? 'selected' : '' }}>
                {{ match ($modalidad) {
                    'libre_circulacion' => 'Libre circulación',
                    'solo_sala' => 'Solo sala',
                    'restringido_a_autorizacion' => 'Restringido a autorización',
                } }}
            </option>
        @endforeach
    </select>
    @error('modalidad_acceso') <p class="text-red-600 text-xs mt-1">{{ $message }}</p> @enderror
</div>

<div>
    <label class="block text-sm mb-1">Estado manual (opcional)</label>
    <select name="estado_manual" class="w-full border-gray-300 rounded">
        <option value="">— Sin estado manual (disponible o según movimiento) —</option>
        @foreach (\App\Models\Ejemplar::ESTADOS_MANUALES as $estado)
            <option value="{{ $estado }}" {{ (string) old('estado_manual', $ejemplar->estado_manual ?? '') === $estado ? 'selected' : '' }}>
                {{ $estado === 'en_reparacion' ? 'En reparación' : 'Extraviado' }}
            </option>
        @endforeach
    </select>
    @error('estado_manual') <p class="text-red-600 text-xs mt-1">{{ $message }}</p> @enderror
</div>

<div class="grid grid-cols-2 gap-4">
    <div>
        <label class="block text-sm mb-1">Fecha de ingreso</label>
        <input type="date" name="fecha_ingreso"
               value="{{ old('fecha_ingreso', isset($ejemplar) ? $ejemplar->fecha_ingreso->format('Y-m-d') : now()->format('Y-m-d')) }}"
               class="w-full border-gray-300 rounded" required>
        @error('fecha_ingreso') <p class="text-red-600 text-xs mt-1">{{ $message }}</p> @enderror
    </div>
    <div>
        <label class="block text-sm mb-1">Origen</label>
        <select name="origen" class="w-full border-gray-300 rounded" required>
            @foreach (\App\Models\Ejemplar::ORIGENES as $origen)
                <option value="{{ $origen }}" {{ old('origen', $ejemplar->origen ?? \App\Models\Ejemplar::ORIGEN_COMPRA) === $origen ? 'selected' : '' }}>
                    {{ ucfirst($origen) }}
                </option>
            @endforeach
        </select>
        @error('origen') <p class="text-red-600 text-xs mt-1">{{ $message }}</p> @enderror
    </div>
</div>

<div>
    <label class="block text-sm mb-1">Condición física (opcional)</label>
    <textarea name="condicion_fisica" class="w-full border-gray-300 rounded" rows="3">{{ old('condicion_fisica', $ejemplar->condicion_fisica ?? '') }}</textarea>
    @error('condicion_fisica') <p class="text-red-600 text-xs mt-1">{{ $message }}</p> @enderror
</div>
