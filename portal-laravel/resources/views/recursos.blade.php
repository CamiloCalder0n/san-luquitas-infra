@extends('layouts.app')

@section('titulo', 'Recursos')

@php
    $recursos = [
        [
            'nombre' => 'Scratch',
            'url' => 'https://scratch.mit.edu/',
            'texto' => 'Crea historias, juegos y animaciones juntando bloques de colores.',
        ],
        [
            'nombre' => 'Code.org',
            'url' => 'https://code.org/',
            'texto' => 'Cursos cortos y la Hora del Código para aprender paso a paso.',
        ],
        [
            'nombre' => 'ScratchJr',
            'url' => 'https://www.scratchjr.org/',
            'texto' => 'La versión de Scratch para los más pequeños, en tableta.',
        ],
        [
            'nombre' => 'Blockly Games',
            'url' => 'https://blockly.games/?lang=es',
            'texto' => 'Juegos con bloques que enseñan a pensar como programador.',
        ],
        [
            'nombre' => 'CS Unplugged',
            'url' => 'https://www.csunplugged.org/es/',
            'texto' => 'Actividades sin computador para aprender ciencias de la computación.',
        ],
    ];
@endphp

@section('contenido')
    <h1 class="text-2xl font-bold mb-2">Recursos de programación</h1>
    <p class="text-stone-700 mb-6">Páginas gratuitas para practicar en clase o en la casa.</p>

    <div class="grid gap-4 sm:grid-cols-2 lg:grid-cols-3">
        @foreach ($recursos as $recurso)
            <a href="{{ $recurso['url'] }}" target="_blank" rel="noopener noreferrer"
               class="block rounded-lg border border-stone-200 bg-white p-5 shadow-sm hover:border-emerald-300">
                <h2 class="font-semibold mb-1">{{ $recurso['nombre'] }}</h2>
                <p class="text-sm text-stone-600">{{ $recurso['texto'] }}</p>
                <span class="mt-3 inline-block text-sm text-emerald-700">Abrir &rarr;</span>
            </a>
        @endforeach
    </div>
@endsection
