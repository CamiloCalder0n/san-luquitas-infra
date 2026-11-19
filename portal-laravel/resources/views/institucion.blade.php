@extends('layouts.app')

@section('titulo', 'Institución')

@section('contenido')
    <h1 class="text-2xl font-bold mb-4">Nuestra institución</h1>

    <p class="text-stone-700 mb-8 max-w-3xl">
        La Institución Educativa San Luquitas es un colegio rural que atiende a niños y niñas de las veredas
        cercanas. Queremos que nuestros estudiantes aprendan con las herramientas de hoy sin perder lo que
        nos enseña el campo.
    </p>

    <div class="grid gap-4 sm:grid-cols-2 mb-8">
        <section class="rounded-lg border border-stone-200 bg-white p-5 shadow-sm">
            <h2 class="font-semibold mb-2">Misión</h2>
            <p class="text-sm text-stone-600">
                Formar estudiantes respetuosos, curiosos y responsables, con una educación de calidad que use la
                tecnología para aprender y para ayudar a su comunidad.
            </p>
        </section>
        <section class="rounded-lg border border-stone-200 bg-white p-5 shadow-sm">
            <h2 class="font-semibold mb-2">Visión</h2>
            <p class="text-sm text-stone-600">
                En el 2030 ser un colegio rural reconocido en la región por enseñar programación y ciencias desde
                la primaria, con estudiantes que cuidan su entorno.
            </p>
        </section>
    </div>

    <section class="rounded-lg border border-stone-200 bg-white p-5 shadow-sm">
        <h2 class="font-semibold mb-3">Datos generales</h2>
        <dl class="grid gap-3 sm:grid-cols-2 text-sm">
            <div>
                <dt class="text-stone-500">Sede</dt>
                <dd>Sede rural principal, vereda La Esperanza</dd>
            </div>
            <div>
                <dt class="text-stone-500">Jornada</dt>
                <dd>Mañana, de 7:00 a. m. a 12:30 p. m.</dd>
            </div>
            <div>
                <dt class="text-stone-500">Niveles</dt>
                <dd>Preescolar y básica primaria (transición a 5.°)</dd>
            </div>
            <div>
                <dt class="text-stone-500">Carácter</dt>
                <dd>Público, mixto</dd>
            </div>
        </dl>
        <p class="mt-4 text-xs text-stone-500">Todos los datos de esta página son ficticios.</p>
    </section>
@endsection
