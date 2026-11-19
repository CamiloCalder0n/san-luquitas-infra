@extends('layouts.app')

@section('titulo', 'Novedades')

@section('contenido')
    <div class="flex flex-wrap items-center justify-between gap-4 mb-6">
        <h1 class="text-2xl font-bold">Novedades</h1>
        <a href="{{ route('novedades.create') }}"
           class="rounded-md bg-emerald-700 px-4 py-2 text-sm font-medium text-white hover:bg-emerald-800">Nueva novedad</a>
    </div>

    <div class="flex flex-wrap gap-2 mb-6 text-sm">
        <a href="{{ route('novedades.index') }}"
           class="rounded-full border px-3 py-1 {{ $categoria === null ? 'bg-emerald-700 text-white border-emerald-700' : 'border-stone-300 hover:bg-stone-100' }}">Todas</a>
        @foreach (\App\Models\Novedad::CATEGORIAS as $valor => $nombre)
            <a href="{{ route('novedades.index', ['categoria' => $valor]) }}"
               class="rounded-full border px-3 py-1 {{ $categoria === $valor ? 'bg-emerald-700 text-white border-emerald-700' : 'border-stone-300 hover:bg-stone-100' }}">{{ $nombre }}</a>
        @endforeach
    </div>

    @if ($novedades->isEmpty())
        <p class="text-stone-500">No hay novedades para mostrar.</p>
    @else
        <div class="grid gap-4 sm:grid-cols-2 lg:grid-cols-3">
            @foreach ($novedades as $novedad)
                <article class="flex flex-col rounded-lg border border-stone-200 bg-white p-4 shadow-sm">
                    <div class="flex items-center justify-between text-xs text-stone-500 mb-2">
                        <span class="rounded bg-emerald-50 px-2 py-0.5 text-emerald-800">{{ $novedad->nombreCategoria() }}</span>
                        <span>{{ $novedad->fecha_publicacion->format('d/m/Y') }}</span>
                    </div>
                    <h2 class="font-semibold mb-1">
                        <a href="{{ route('novedades.show', $novedad) }}" class="hover:underline">{{ $novedad->titulo }}</a>
                    </h2>
                    <p class="text-sm text-stone-600 flex-1">{{ $novedad->resumen }}</p>

                    <div class="mt-4 flex items-center gap-3 text-sm">
                        <a href="{{ route('novedades.show', $novedad) }}" class="text-emerald-700 hover:underline">Ver</a>
                        <a href="{{ route('novedades.edit', $novedad) }}" class="text-stone-600 hover:underline">Editar</a>
                        <form action="{{ route('novedades.destroy', $novedad) }}" method="POST"
                              onsubmit="return confirm('¿Seguro que quieres eliminar esta novedad?');">
                            @csrf
                            @method('DELETE')
                            <button type="submit" class="text-red-700 hover:underline">Eliminar</button>
                        </form>
                    </div>
                </article>
            @endforeach
        </div>

        <div class="mt-6">
            {{ $novedades->links() }}
        </div>
    @endif
@endsection
