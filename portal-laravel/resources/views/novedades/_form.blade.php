{{-- Formulario compartido de crear y editar. Recibe $novedad. --}}
@if ($errors->any())
    <div class="mb-6 rounded-md border border-red-200 bg-red-50 px-4 py-3 text-sm text-red-800" role="alert">
        Revisa los campos marcados.
    </div>
@endif

<div class="space-y-5">
    <div>
        <label for="titulo" class="block text-sm font-medium mb-1">Título</label>
        <input type="text" id="titulo" name="titulo" maxlength="150" required
               value="{{ old('titulo', $novedad->titulo) }}"
               class="w-full rounded-md border border-stone-300 px-3 py-2 @error('titulo') border-red-500 @enderror">
        @error('titulo') <p class="mt-1 text-sm text-red-700">{{ $message }}</p> @enderror
    </div>

    <div>
        <label for="resumen" class="block text-sm font-medium mb-1">Resumen</label>
        <input type="text" id="resumen" name="resumen" maxlength="255" required
               value="{{ old('resumen', $novedad->resumen) }}"
               class="w-full rounded-md border border-stone-300 px-3 py-2 @error('resumen') border-red-500 @enderror">
        @error('resumen') <p class="mt-1 text-sm text-red-700">{{ $message }}</p> @enderror
    </div>

    <div>
        <label for="contenido" class="block text-sm font-medium mb-1">Contenido</label>
        <textarea id="contenido" name="contenido" rows="6" required
                  class="w-full rounded-md border border-stone-300 px-3 py-2 @error('contenido') border-red-500 @enderror">{{ old('contenido', $novedad->contenido) }}</textarea>
        @error('contenido') <p class="mt-1 text-sm text-red-700">{{ $message }}</p> @enderror
    </div>

    <div class="grid gap-5 sm:grid-cols-2">
        <div>
            <label for="categoria" class="block text-sm font-medium mb-1">Categoría</label>
            <select id="categoria" name="categoria" required
                    class="w-full rounded-md border border-stone-300 px-3 py-2 bg-white @error('categoria') border-red-500 @enderror">
                <option value="">Selecciona una</option>
                @foreach (\App\Models\Novedad::CATEGORIAS as $valor => $nombre)
                    <option value="{{ $valor }}" @selected(old('categoria', $novedad->categoria) === $valor)>{{ $nombre }}</option>
                @endforeach
            </select>
            @error('categoria') <p class="mt-1 text-sm text-red-700">{{ $message }}</p> @enderror
        </div>

        <div>
            <label for="fecha_publicacion" class="block text-sm font-medium mb-1">Fecha de publicación</label>
            <input type="date" id="fecha_publicacion" name="fecha_publicacion" required
                   value="{{ old('fecha_publicacion', $novedad->fecha_publicacion?->format('Y-m-d')) }}"
                   class="w-full rounded-md border border-stone-300 px-3 py-2 @error('fecha_publicacion') border-red-500 @enderror">
            @error('fecha_publicacion') <p class="mt-1 text-sm text-red-700">{{ $message }}</p> @enderror
        </div>
    </div>
</div>
