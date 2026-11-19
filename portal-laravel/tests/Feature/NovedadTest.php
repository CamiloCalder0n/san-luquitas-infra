<?php

namespace Tests\Feature;

use App\Models\Novedad;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class NovedadTest extends TestCase
{
    use RefreshDatabase;

    private function datosValidos(array $cambios = []): array
    {
        return array_merge([
            'titulo' => 'Prueba de novedad',
            'resumen' => 'Resumen corto de prueba.',
            'contenido' => 'Contenido de prueba.',
            'categoria' => 'noticia',
            'fecha_publicacion' => '2026-11-01',
        ], $cambios);
    }

    public function test_el_listado_pagina_de_6_en_6_con_las_mas_recientes_primero(): void
    {
        Novedad::factory()->count(7)->create(['fecha_publicacion' => '2026-01-01']);
        Novedad::factory()->create(['titulo' => 'La más reciente', 'fecha_publicacion' => '2026-11-10']);

        $respuesta = $this->get(route('novedades.index'));

        $respuesta->assertOk();
        $this->assertCount(6, $respuesta->viewData('novedades'));
        $this->assertSame('La más reciente', $respuesta->viewData('novedades')->first()->titulo);
    }

    public function test_el_listado_filtra_por_categoria(): void
    {
        Novedad::factory()->create(['titulo' => 'Un evento', 'categoria' => 'evento']);
        Novedad::factory()->create(['titulo' => 'Un comunicado', 'categoria' => 'comunicado']);

        $this->get(route('novedades.index', ['categoria' => 'evento']))
            ->assertOk()
            ->assertSee('Un evento')
            ->assertDontSee('Un comunicado');
    }

    public function test_una_categoria_invalida_en_el_filtro_muestra_todas(): void
    {
        Novedad::factory()->create(['titulo' => 'Un evento', 'categoria' => 'evento']);

        $this->get('/novedades?categoria[]=x')->assertOk()->assertSee('Un evento');
        $this->get('/novedades?categoria=otra')->assertOk()->assertSee('Un evento');
    }

    public function test_crea_una_novedad(): void
    {
        $respuesta = $this->post(route('novedades.store'), $this->datosValidos());

        $novedad = Novedad::firstWhere('titulo', 'Prueba de novedad');
        $this->assertNotNull($novedad);
        $respuesta->assertRedirect(route('novedades.show', $novedad))
            ->assertSessionHas('ok', 'Novedad creada.');
    }

    public function test_la_validacion_muestra_mensajes_en_espanol(): void
    {
        $this->post(route('novedades.store'), [])
            ->assertSessionHasErrors([
                'titulo' => 'El campo título es obligatorio.',
                'fecha_publicacion' => 'El campo fecha de publicación es obligatorio.',
            ]);

        $this->post(route('novedades.store'), $this->datosValidos([
            'categoria' => 'otra',
            'titulo' => str_repeat('a', 151),
            'contenido' => str_repeat('a', 10001),
        ]))
            ->assertSessionHasErrors([
                'categoria' => 'La categoría debe ser noticia, comunicado o evento.',
                'titulo' => 'El campo título no puede tener más de 150 caracteres.',
                'contenido' => 'El campo contenido no puede tener más de 10000 caracteres.',
            ]);

        $this->assertSame(0, Novedad::count());
    }

    public function test_muestra_edita_y_elimina_una_novedad(): void
    {
        $novedad = Novedad::factory()->create();

        $this->get(route('novedades.show', $novedad))->assertOk()->assertSee($novedad->titulo);
        $this->get(route('novedades.edit', $novedad))->assertOk();

        $this->put(route('novedades.update', $novedad), $this->datosValidos(['titulo' => 'Título editado']))
            ->assertRedirect(route('novedades.show', $novedad))
            ->assertSessionHas('ok', 'Novedad actualizada.');
        $this->assertSame('Título editado', $novedad->fresh()->titulo);

        $this->delete(route('novedades.destroy', $novedad))
            ->assertRedirect(route('novedades.index'))
            ->assertSessionHas('ok', 'Novedad eliminada.');
        $this->assertModelMissing($novedad);
    }

    public function test_el_seeder_crea_6_novedades_y_no_duplica(): void
    {
        $this->seed();
        $this->seed();

        $this->assertSame(6, Novedad::count());
    }
}
