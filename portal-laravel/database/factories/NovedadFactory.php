<?php

namespace Database\Factories;

use App\Models\Novedad;
use Illuminate\Database\Eloquent\Factories\Factory;

/**
 * Solo para pruebas (Faker no se instala en la imagen de producción).
 *
 * @extends Factory<Novedad>
 */
class NovedadFactory extends Factory
{
    public function definition(): array
    {
        return [
            'titulo' => fake('es_ES')->sentence(4),
            'resumen' => fake('es_ES')->sentence(10),
            'contenido' => fake('es_ES')->paragraphs(2, true),
            'categoria' => fake()->randomElement(array_keys(Novedad::CATEGORIAS)),
            'fecha_publicacion' => fake()->dateTimeBetween('-2 months', 'now')->format('Y-m-d'),
        ];
    }
}
