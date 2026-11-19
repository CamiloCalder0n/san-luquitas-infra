@extends('layouts.app')

@section('titulo', 'Inicio')

@section('contenido')
    <section class="rounded-lg bg-emerald-50 border border-emerald-100 p-6 mb-8">
        <h1 class="text-2xl sm:text-3xl font-bold text-emerald-900 mb-2">Bienvenidos a la IE San Luquitas</h1>
        <p class="text-stone-700 max-w-2xl">
            Este es el portal de nuestro colegio. Aquí encuentran las novedades de la sede, información de la
            institución y recursos para aprender a programar.
        </p>
    </section>

    <section class="grid gap-4 sm:grid-cols-2 mb-10">
        <a href="{{ config('app.moodle_url') }}"
           class="block rounded-lg border border-stone-200 bg-white p-5 shadow-sm hover:border-emerald-300">
            <h2 class="font-semibold mb-1">San Luquitas Digital</h2>
            <p class="text-sm text-stone-600">Entra a la plataforma de clases y al curso "Mis primeros pasos en programación".</p>
        </a>
        <a href="{{ route('recursos') }}"
           class="block rounded-lg border border-stone-200 bg-white p-5 shadow-sm hover:border-emerald-300">
            <h2 class="font-semibold mb-1">Recursos de programación</h2>
            <p class="text-sm text-stone-600">Páginas para practicar programación con bloques, juegos y actividades.</p>
        </a>
    </section>

    <section>
        <div class="flex items-center justify-between mb-4">
            <h2 class="text-xl font-bold">Últimas novedades</h2>
            <a href="{{ route('novedades.index') }}" class="text-sm text-emerald-700 hover:underline">Ver todas</a>
        </div>

        @if ($ultimas->isEmpty())
            <p class="text-stone-500">Todavía no hay novedades.</p>
        @else
            <div class="grid gap-4 sm:grid-cols-3">
                @foreach ($ultimas as $novedad)
                    <article class="rounded-lg border border-stone-200 bg-white p-4 shadow-sm">
                        <div class="flex items-center justify-between text-xs text-stone-500 mb-2">
                            <span class="rounded bg-emerald-50 px-2 py-0.5 text-emerald-800">{{ $novedad->nombreCategoria() }}</span>
                            <span>{{ $novedad->fecha_publicacion->format('d/m/Y') }}</span>
                        </div>
                        <h3 class="font-semibold mb-1">
                            <a href="{{ route('novedades.show', $novedad) }}" class="hover:underline">{{ $novedad->titulo }}</a>
                        </h3>
                        <p class="text-sm text-stone-600">{{ $novedad->resumen }}</p>
                    </article>
                @endforeach
            </div>
        @endif
    </section>
@endsection
