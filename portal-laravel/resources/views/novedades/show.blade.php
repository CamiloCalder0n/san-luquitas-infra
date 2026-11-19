@extends('layouts.app')

@section('titulo', $novedad->titulo)

@section('contenido')
    <a href="{{ route('novedades.index') }}" class="text-sm text-emerald-700 hover:underline">&larr; Volver a novedades</a>

    <article class="mt-4 rounded-lg border border-stone-200 bg-white p-6 shadow-sm">
        <div class="flex items-center gap-3 text-xs text-stone-500 mb-3">
            <span class="rounded bg-emerald-50 px-2 py-0.5 text-emerald-800">{{ $novedad->nombreCategoria() }}</span>
            <span>{{ $novedad->fecha_publicacion->format('d/m/Y') }}</span>
        </div>

        <h1 class="text-2xl font-bold mb-2">{{ $novedad->titulo }}</h1>
        <p class="text-stone-600 mb-4">{{ $novedad->resumen }}</p>
        <div class="whitespace-pre-line leading-relaxed">{{ $novedad->contenido }}</div>

        <div class="mt-6 flex items-center gap-4 text-sm">
            <a href="{{ route('novedades.edit', $novedad) }}"
               class="rounded-md border border-stone-300 px-3 py-1.5 hover:bg-stone-100">Editar</a>
            <form action="{{ route('novedades.destroy', $novedad) }}" method="POST"
                  onsubmit="return confirm('¿Seguro que quieres eliminar esta novedad?');">
                @csrf
                @method('DELETE')
                <button type="submit" class="rounded-md border border-red-300 px-3 py-1.5 text-red-700 hover:bg-red-50">Eliminar</button>
            </form>
        </div>
    </article>
@endsection
