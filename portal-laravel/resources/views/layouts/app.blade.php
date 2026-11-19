<!DOCTYPE html>
<html lang="{{ str_replace('_', '-', app()->getLocale()) }}">
<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <title>@yield('titulo', 'Inicio') · IE San Luquitas</title>
    @vite(['resources/css/app.css', 'resources/js/app.js'])
</head>
<body class="min-h-screen flex flex-col bg-stone-50 text-stone-800">
    <header class="bg-emerald-700 text-white">
        <div class="max-w-5xl mx-auto px-4 py-4 flex flex-wrap items-center justify-between gap-3">
            <a href="{{ route('inicio') }}" class="text-lg font-semibold">IE San Luquitas</a>

            <nav class="flex flex-wrap items-center gap-4 text-sm">
                @foreach ([
                    'inicio' => 'Inicio',
                    'institucion' => 'Institución',
                    'recursos' => 'Recursos',
                    'novedades.index' => 'Novedades',
                ] as $ruta => $texto)
                    <a href="{{ route($ruta) }}"
                       class="hover:underline {{ request()->routeIs(str_replace('.index', '.*', $ruta)) ? 'font-semibold underline' : '' }}">{{ $texto }}</a>
                @endforeach
                <a href="{{ config('app.moodle_url') }}"
                   class="rounded-md bg-white px-3 py-1.5 font-medium text-emerald-800 hover:bg-emerald-50">Ir a San Luquitas Digital</a>
            </nav>
        </div>
    </header>

    <main class="flex-1 w-full max-w-5xl mx-auto px-4 py-8">
        @if (session('ok'))
            <div class="mb-6 rounded-md border border-emerald-200 bg-emerald-50 px-4 py-3 text-emerald-800" role="status">
                {{ session('ok') }}
            </div>
        @endif

        @yield('contenido')
    </main>

    <footer class="border-t border-stone-200 bg-white">
        <p class="max-w-5xl mx-auto px-4 py-4 text-sm text-stone-500">
            IE San Luquitas – información ficticia con fines académicos
        </p>
    </footer>
</body>
</html>
