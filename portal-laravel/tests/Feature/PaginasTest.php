<?php

namespace Tests\Feature;

use App\Models\Novedad;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class PaginasTest extends TestCase
{
    use RefreshDatabase;

    public function test_el_inicio_muestra_las_3_ultimas_novedades(): void
    {
        Novedad::factory()->create(['titulo' => 'Novedad vieja', 'fecha_publicacion' => '2026-01-01']);
        foreach (['2026-11-01', '2026-11-02', '2026-11-03'] as $i => $fecha) {
            Novedad::factory()->create(['titulo' => "Novedad reciente $i", 'fecha_publicacion' => $fecha]);
        }

        $this->get(route('inicio'))
            ->assertOk()
            ->assertSeeInOrder(['Novedad reciente 2', 'Novedad reciente 1', 'Novedad reciente 0'])
            ->assertDontSee('Novedad vieja');
    }

    public function test_el_menu_tiene_todas_las_paginas_y_el_enlace_a_moodle(): void
    {
        config(['app.moodle_url' => 'http://moodle.prueba']);

        // Institución no tiene enlaces en el cuerpo: lo que se ve aquí viene del layout.
        $this->get(route('institucion'))
            ->assertOk()
            ->assertSeeInOrder([
                'href="'.route('inicio').'"', '>Inicio<',
                'href="'.route('institucion').'"', '>Institución<',
                'href="'.route('recursos').'"', '>Recursos<',
                'href="'.route('novedades.index').'"', '>Novedades<',
                'href="http://moodle.prueba"', '>Ir a San Luquitas Digital<',
            ], false)
            ->assertSee('información ficticia con fines académicos');
    }

    public function test_la_pagina_de_institucion_carga(): void
    {
        $this->get(route('institucion'))
            ->assertOk()
            ->assertSee('Misión')
            ->assertSee('Visión')
            ->assertSee('Datos generales');
    }

    public function test_la_pagina_de_recursos_tiene_los_5_enlaces(): void
    {
        $respuesta = $this->get(route('recursos'))->assertOk();

        foreach (['scratch.mit.edu', 'code.org', 'scratchjr.org', 'blockly.games', 'csunplugged.org'] as $sitio) {
            $respuesta->assertSee($sitio);
        }
    }
}
