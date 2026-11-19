@extends('layouts.app')

@section('titulo', 'Editar novedad')

@section('contenido')
    <a href="{{ route('novedades.show', $novedad) }}" class="text-sm text-emerald-700 hover:underline">&larr; Volver a la novedad</a>

    <h1 class="text-2xl font-bold mt-4 mb-6">Editar novedad</h1>

    <form action="{{ route('novedades.update', $novedad) }}" method="POST" class="rounded-lg border border-stone-200 bg-white p-6 shadow-sm">
        @csrf
        @method('PUT')
        @include('novedades._form')

        <div class="mt-6 flex items-center gap-3">
            <button type="submit" class="rounded-md bg-emerald-700 px-4 py-2 text-sm font-medium text-white hover:bg-emerald-800">Guardar cambios</button>
            <a href="{{ route('novedades.show', $novedad) }}" class="text-sm text-stone-600 hover:underline">Cancelar</a>
        </div>
    </form>
@endsection
