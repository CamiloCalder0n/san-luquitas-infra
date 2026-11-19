<?php

namespace Database\Seeders;

use App\Models\Novedad;
use Illuminate\Database\Seeder;

/**
 * Novedades ficticias del colegio. Datos fijos (sin Faker) para que funcione
 * en la imagen de producción. Se puede correr varias veces sin duplicar.
 */
class NovedadSeeder extends Seeder
{
    public function run(): void
    {
        $novedades = [
            [
                'titulo' => 'Inicio de clases del segundo semestre',
                'resumen' => 'Las clases empiezan el lunes en la jornada de la mañana para todos los grados.',
                'contenido' => "Les recordamos a las familias que las clases del segundo semestre empiezan el lunes a las 7:00 a. m.\n\nLos estudiantes deben traer cuaderno, lápiz y su botella de agua. El transporte escolar de la vereda pasa a la hora de siempre.",
                'categoria' => 'comunicado',
                'dias' => 30,
            ],
            [
                'titulo' => 'Nace el club de programación con Scratch',
                'resumen' => 'Los estudiantes de 4.° y 5.° pueden inscribirse al nuevo club de programación de los viernes.',
                'contenido' => "Este semestre abrimos el club de programación con Scratch para 4.° y 5.° de primaria.\n\nNos vemos los viernes después del descanso en la sala de informática. No hace falta saber programar: vamos a crear juegos y animaciones paso a paso.",
                'categoria' => 'noticia',
                'dias' => 24,
            ],
            [
                'titulo' => 'Entrega de boletines del primer periodo',
                'resumen' => 'La entrega de boletines será el sábado en el salón comunal de la sede.',
                'contenido' => "Las familias pueden recoger los boletines del primer periodo el sábado de 8:00 a 11:00 a. m. en el salón comunal.\n\nCada docente estará en su mesa para resolver dudas sobre el proceso de los estudiantes.",
                'categoria' => 'comunicado',
                'dias' => 18,
            ],
            [
                'titulo' => 'Feria de la ciencia escolar',
                'resumen' => 'Los grupos presentarán sus experimentos sobre el agua y las plantas de la vereda.',
                'contenido' => "La feria de la ciencia será en la cancha de la sede. Cada grupo mostrará un experimento sobre el agua, el suelo o las plantas de la región.\n\nLas familias y la comunidad están invitadas a visitar los puestos.",
                'categoria' => 'evento',
                'dias' => 12,
            ],
            [
                'titulo' => 'Jornada de vacunación de mascotas en la vereda',
                'resumen' => 'La sede será punto de vacunación gratuita para perros y gatos de la comunidad.',
                'contenido' => "Con apoyo de la alcaldía habrá jornada de vacunación gratuita para perros y gatos.\n\nLas mascotas deben venir con collar o en guacal. La jornada será en el patio de la sede de 9:00 a. m. a 1:00 p. m.",
                'categoria' => 'evento',
                'dias' => 6,
            ],
            [
                'titulo' => 'Llegan nuevos computadores a la sala de informática',
                'resumen' => 'La sala de informática recibió equipos nuevos para las clases de tecnología.',
                'contenido' => "La sede recibió nuevos computadores para la sala de informática.\n\nCon ellos vamos a trabajar el curso \"Mis primeros pasos en programación\" en la plataforma San Luquitas Digital.",
                'categoria' => 'noticia',
                'dias' => 2,
            ],
        ];

        foreach ($novedades as $datos) {
            $dias = $datos['dias'];
            unset($datos['dias']);
            $datos['fecha_publicacion'] = now()->subDays($dias)->toDateString();

            Novedad::updateOrCreate(['titulo' => $datos['titulo']], $datos);
        }
    }
}
