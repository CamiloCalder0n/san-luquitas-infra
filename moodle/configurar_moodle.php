<?php
// Configura San Luquitas Digital: identidad, portada, curso, contenido y usuarios.
// Uso (en el servidor):
//   sudo -u www-data php /opt/san-luquitas-infra/moodle/configurar_moodle.php
// Se puede correr varias veces: lo que ya existe no se duplica.
// Ruta de Moodle distinta: MOODLE_CONFIG=/ruta/config.php sudo -E -u www-data php ...

define('CLI_SCRIPT', true);

$configfile = getenv('MOODLE_CONFIG') ?: '/var/www/moodle/config.php';
require($configfile);
require_once($CFG->dirroot . '/course/lib.php');
require_once($CFG->dirroot . '/course/modlib.php');
require_once($CFG->dirroot . '/user/lib.php');
require_once($CFG->dirroot . '/mod/quiz/locallib.php');
require_once($CFG->libdir . '/filelib.php');

$dir = __DIR__;
$identidad = $dir . '/identidad';
$fs = get_file_storage();
$syscontext = context_system::instance();
$CFG->noemailever = true; // No mandar correos al crear usuarios (no hay servidor de correo).
\core\session\manager::set_user(get_admin());
$gen = \core\test\phpunit\phpunit_util::get_data_generator();

function paso(string $texto): void {
    echo "- {$texto}\n";
}

function guardar_archivo(int $contextid, string $component, string $filearea, string $ruta, string $nombre): void {
    global $fs;
    $fs->delete_area_files($contextid, $component, $filearea, 0);
    $fs->create_file_from_pathname([
        'contextid' => $contextid, 'component' => $component, 'filearea' => $filearea,
        'itemid' => 0, 'filepath' => '/', 'filename' => $nombre,
    ], $ruta);
}

echo "Configurando San Luquitas Digital\n";

// 1. Nombre del sitio y página de inicio.
$site = get_site();
$DB->update_record('course', (object) [
    'id' => $site->id,
    'fullname' => 'San Luquitas Digital',
    'shortname' => 'San Luquitas Digital',
    'summary' => 'Entorno Virtual de Aprendizaje de la IE San Luquitas para aprender programación y pensamiento computacional.',
    'summaryformat' => FORMAT_HTML,
]);
set_config('enablemyhome', 1); // En Moodle 5.3 la página principal viene apagada.
set_config('defaulthomepage', HOMEPAGE_SITE);
paso('Nombre del sitio y página de inicio');

// 2. Logos.
guardar_archivo($syscontext->id, 'core_admin', 'logo', "$identidad/logo.png", 'logo.png');
guardar_archivo($syscontext->id, 'core_admin', 'logocompact', "$identidad/logo_compacto.png", 'logo_compacto.png');
set_config('logo', '/logo.png', 'core_admin');
set_config('logocompact', '/logo_compacto.png', 'core_admin');
paso('Logos');

// 3. Tema Boost: color de marca y estilos.
set_config('brandcolor', '#2E7D32', 'theme_boost');
set_config('scss', file_get_contents("$identidad/estilos_boost.scss"), 'theme_boost');
paso('Colores y estilos del tema');

// 4. Curso.
$course = $DB->get_record('course', ['shortname' => 'PROG45']);
if (!$course) {
    $course = create_course((object) [
        'category' => core_course_category::get_default()->id,
        'fullname' => 'Mis primeros pasos en programación',
        'shortname' => 'PROG45',
        'summary' => '<p>Curso para estudiantes de 4.° y 5.° de primaria de la IE San Luquitas. En tres unidades cortas '
            . 'los niños aprenden a pensar como programadores: siguen y crean secuencias, encuentran patrones, escriben '
            . 'algoritmos de la vida diaria y hacen sus primeros programas en Scratch.</p>'
            . '<p><strong>Población:</strong> niños y niñas de 9 a 11 años.</p>'
            . '<p><strong>Objetivos de aprendizaje:</strong></p><ol>'
            . '<li>Reconocer secuencias y patrones en situaciones cotidianas.</li>'
            . '<li>Escribir instrucciones claras y ordenadas para resolver un problema.</li>'
            . '<li>Crear un programa sencillo con bloques en Scratch.</li></ol>',
        'summaryformat' => FORMAT_HTML,
        'format' => 'topics',
        'numsections' => 3,
        'enablecompletion' => 1,
    ]);
    paso('Curso PROG45 creado');
} else {
    paso('Curso PROG45 ya existía');
}
$coursecontext = context_course::instance($course->id);
guardar_archivo($coursecontext->id, 'course', 'overviewfiles', "$identidad/imagen_curso.png", 'imagen_curso.png');
course_create_sections_if_missing($course, [0, 1, 2, 3]);

$unidades = [
    1 => ['Unidad 1: Introducción al pensamiento computacional: secuencias y patrones',
        'Una secuencia es una serie de pasos en orden. Un patrón es algo que se repite. En esta unidad aprendemos a verlos en todas partes.'],
    2 => ['Unidad 2: Algoritmos cotidianos: instrucciones y resolución de problemas',
        'Un algoritmo es una receta: pasos claros y en orden para resolver un problema.'],
    3 => ['Unidad 3: Programación visual: primeros pasos con Scratch',
        'Scratch es un programa para crear juegos e historias uniendo bloques de colores.'],
];
foreach ($unidades as $num => [$nombre, $resumen]) {
    $section = $DB->get_record('course_sections', ['course' => $course->id, 'section' => $num]);
    course_update_section($course, $section, ['name' => $nombre, 'summary' => "<p>$resumen</p>", 'summaryformat' => FORMAT_HTML]);
}
paso('Nombres y resúmenes de las 3 unidades');

// 5. Actividades y recursos (solo se crean si no existen con ese nombre).
function existe(string $modulo, int $courseid, string $nombre): bool {
    global $DB;
    return $DB->record_exists($modulo, ['course' => $courseid, 'name' => $nombre]);
}

$base = ['course' => $course->id, 'introformat' => FORMAT_HTML];

if (!existe('forum', $course->id, 'Cuéntanos qué aprendiste')) {
    $gen->create_module('forum', $base + ['section' => 0, 'type' => 'general', 'name' => 'Cuéntanos qué aprendiste',
        'intro' => '<p>Escribe qué fue lo que más te gustó de la clase y comparte una secuencia o patrón que encontraste en tu casa o en la vereda.</p>']);
}

if (!existe('page', $course->id, '¿Qué es una secuencia?')) {
    $gen->create_module('page', $base + ['section' => 1, 'name' => '¿Qué es una secuencia?',
        'intro' => '<p>Lectura de la unidad 1.</p>', 'contentformat' => FORMAT_HTML,
        'content' => '<p>Una <strong>secuencia</strong> es una lista de pasos que se hacen en un orden. Si cambiamos el orden, el resultado cambia.</p>'
            . '<h4>Ejemplo: sembrar una semilla</h4><ol><li>Hacer un hueco en la tierra.</li><li>Poner la semilla.</li>'
            . '<li>Tapar con tierra.</li><li>Echar agua.</li></ol><p>¿Qué pasaría si echamos el agua antes de hacer el hueco?</p>'
            . '<h4>Patrones</h4><p>Un <strong>patrón</strong> es algo que se repite. Por ejemplo: rojo, azul, rojo, azul… ¿Qué color sigue? '
            . 'Los patrones nos ayudan a adivinar lo que viene y a ahorrar trabajo.</p>']);
}

if (!existe('resource', $course->id, 'Ficha: completa el patrón')) {
    $draftid = file_get_unused_draft_itemid();
    $fs->create_file_from_pathname([
        'contextid' => context_user::instance(get_admin()->id)->id, 'component' => 'user', 'filearea' => 'draft',
        'itemid' => $draftid, 'filepath' => '/', 'filename' => 'ficha_patrones.pdf',
    ], "$identidad/ficha_patrones.pdf");
    $gen->create_module('resource', $base + ['section' => 1, 'name' => 'Ficha: completa el patrón',
        'intro' => '<p>Descarga la ficha, complétala en tu cuaderno y compártela con tu profe.</p>', 'files' => $draftid]);
}

$quiz = $DB->get_record('quiz', ['course' => $course->id, 'name' => 'Quiz: secuencias y patrones']);
if (!$quiz) {
    $quiz = $gen->create_module('quiz', $base + ['section' => 1, 'name' => 'Quiz: secuencias y patrones',
        'intro' => '<p>Responde 5 preguntas cortas. Tienes 2 intentos.</p>', 'attempts' => 2, 'grade' => 5, 'sumgrades' => 0,
        'questionsperpage' => 1]);

    $qbank = \core_question\local\bank\question_bank_helper::get_default_open_instance_system_type($course, true);
    $categoria = question_get_default_category(context_module::instance($qbank->id)->id, true);
    $qtype = question_bank::get_qtype('multichoice');

    $preguntas = [
        ['¿Qué es una secuencia?', ['Pasos que se hacen en un orden', 'Un color que se repite', 'Un juego de computador']],
        ['Completa el patrón: rojo, azul, rojo, azul, rojo… ¿qué sigue?', ['Azul', 'Rojo', 'Verde']],
        ['Completa el patrón: 2, 4, 6, 8… ¿qué número sigue?', ['10', '9', '12']],
        ['Para sembrar una semilla, ¿qué va primero?', ['Hacer el hueco en la tierra', 'Echar agua', 'Tapar con tierra']],
        ['¿Para qué nos sirven los patrones?', ['Para adivinar lo que sigue y ahorrar trabajo', 'Para desordenar los pasos', 'No sirven para nada']],
    ];
    foreach ($preguntas as $i => [$texto, $opciones]) {
        $answers = [];
        $fractions = [];
        $feedback = [];
        foreach ($opciones as $k => $op) {
            $answers[] = ['text' => $op, 'format' => FORMAT_PLAIN];
            $fractions[] = $k === 0 ? '1.0' : '0.0';
            $feedback[] = ['text' => $k === 0 ? '¡Muy bien!' : 'Revisa la lectura y vuelve a intentarlo.', 'format' => FORMAT_HTML];
        }
        $nueva = (object) ['qtype' => 'multichoice', 'createdby' => 0, 'idnumber' => null,
            'status' => \core_question\local\bank\question_version_status::QUESTION_STATUS_READY];
        $form = (object) [
            'category' => $categoria->id . ',' . $categoria->contextid,
            'name' => 'Pregunta ' . ($i + 1),
            'questiontext' => ['text' => "<p>$texto</p>", 'format' => FORMAT_HTML],
            'generalfeedback' => ['text' => '', 'format' => FORMAT_HTML],
            'defaultmark' => 1, 'penalty' => 0.3333333,
            'status' => \core_question\local\bank\question_version_status::QUESTION_STATUS_READY,
            'single' => '1', 'shuffleanswers' => 1, 'answernumbering' => 'abc', 'showstandardinstruction' => 0,
            'shownumcorrect' => 1, 'noanswers' => count($opciones),
            'answer' => $answers, 'fraction' => $fractions, 'feedback' => $feedback,
            'correctfeedback' => ['text' => '¡Correcto!', 'format' => FORMAT_HTML],
            'partiallycorrectfeedback' => ['text' => 'Casi.', 'format' => FORMAT_HTML],
            'incorrectfeedback' => ['text' => 'Incorrecto.', 'format' => FORMAT_HTML],
        ];
        $q = $qtype->save_question($nueva, $form);
        quiz_add_quiz_question($q->id, $quiz, 0, 1);
    }
    \mod_quiz\quiz_settings::create($quiz->id)->get_grade_calculator()->recompute_quiz_sumgrades();
}

if (!existe('page', $course->id, 'Algoritmos en la vida diaria')) {
    $gen->create_module('page', $base + ['section' => 2, 'name' => 'Algoritmos en la vida diaria',
        'intro' => '<p>Lectura de la unidad 2.</p>', 'contentformat' => FORMAT_HTML,
        'content' => '<p>Un <strong>algoritmo</strong> es un conjunto de instrucciones claras, en orden y que terminan. '
            . 'Usamos algoritmos todo el tiempo: al lavarnos los dientes, al preparar una arepa o al ir a la escuela.</p>'
            . '<h4>Reglas de un buen algoritmo</h4><ul><li>Cada paso es claro.</li><li>Los pasos van en orden.</li>'
            . '<li>Tiene un final.</li></ul><p><strong>Reto:</strong> escribe el algoritmo para preparar un vaso de aguapanela.</p>']);
}

if (!existe('url', $course->id, 'CS Unplugged: computación sin computador')) {
    $gen->create_module('url', $base + ['section' => 2, 'name' => 'CS Unplugged: computación sin computador',
        'intro' => '<p>Actividades para aprender computación sin usar computador.</p>',
        'externalurl' => 'https://www.csunplugged.org/es/', 'display' => RESOURCELIB_DISPLAY_NEW]);
}

if (!existe('assign', $course->id, 'Mi algoritmo')) {
    $gen->create_module('assign', $base + ['section' => 2, 'name' => 'Mi algoritmo',
        'intro' => '<p>Escribe en 5 a 8 pasos el algoritmo de algo que haces todos los días. Puedes escribirlo o subir una foto de tu cuaderno.</p>',
        'assignsubmission_onlinetext_enabled' => 1, 'assignsubmission_file_enabled' => 1,
        'assignsubmission_file_maxfiles' => 2, 'grade' => 10, 'submissiondrafts' => 0]);
}

if (!existe('page', $course->id, 'Conozcamos Scratch')) {
    $gen->create_module('page', $base + ['section' => 3, 'name' => 'Conozcamos Scratch',
        'intro' => '<p>Lectura de la unidad 3.</p>', 'contentformat' => FORMAT_HTML,
        'content' => '<p>En Scratch damos órdenes a un personaje con <strong>bloques</strong>. Los bloques se encajan como fichas '
            . 'y se ejecutan de arriba hacia abajo, igual que una secuencia.</p><h4>Tu primer programa</h4><ol>'
            . '<li>Arrastra el bloque "al hacer clic en la bandera verde".</li><li>Debajo pon "mover 10 pasos".</li>'
            . '<li>Agrega "decir ¡Hola San Luquitas! por 2 segundos".</li><li>Haz clic en la bandera verde.</li></ol>'
            . '<p><strong>Reto:</strong> usa el bloque "repetir" para que el gato camine y salte 4 veces.</p>']);
}

foreach ([['Scratch', 'https://scratch.mit.edu', 'Página oficial de Scratch para crear proyectos.'],
          ['Ideas para empezar en Scratch', 'https://scratch.mit.edu/ideas', 'Tutoriales cortos para dar los primeros pasos.']] as [$n, $u, $d]) {
    if (!existe('url', $course->id, $n)) {
        $gen->create_module('url', $base + ['section' => 3, 'name' => $n, 'intro' => "<p>$d</p>",
            'externalurl' => $u, 'display' => RESOURCELIB_DISPLAY_NEW]);
    }
}
paso('Recursos y actividades: foro, 3 páginas, ficha PDF, 3 enlaces, quiz de 5 preguntas y tarea');

// 6. Usuarios y matrícula.
$manual = enrol_get_plugin('manual');
$instancia = $DB->get_record('enrol', ['courseid' => $course->id, 'enrol' => 'manual'], '*', MUST_EXIST);
$roles = ['editingteacher' => $DB->get_field('role', 'id', ['shortname' => 'editingteacher']),
          'student' => $DB->get_field('role', 'id', ['shortname' => 'student'])];
$csv = array_map('str_getcsv', file("$dir/usuarios.csv", FILE_IGNORE_NEW_LINES | FILE_SKIP_EMPTY_LINES));
$cabecera = array_shift($csv);
foreach ($csv as $fila) {
    $u = array_combine($cabecera, $fila);
    $user = $DB->get_record('user', ['username' => $u['username'], 'mnethostid' => $CFG->mnet_localhost_id]);
    if (!$user) {
        $id = user_create_user((object) [
            'username' => $u['username'], 'password' => $u['password'], 'auth' => 'manual', 'confirmed' => 1,
            'mnethostid' => $CFG->mnet_localhost_id, 'firstname' => $u['firstname'], 'lastname' => $u['lastname'],
            'email' => $u['email'], 'lang' => 'es', 'city' => 'San Luquitas', 'country' => 'CO',
        ], true, false);
        $user = $DB->get_record('user', ['id' => $id]);
    }
    $manual->enrol_user($instancia, $user->id, $roles[$u['role1']]);
}
paso('Usuarios docente1, estudiante1, estudiante2 y estudiante3 matriculados en PROG45');

// 7. Portada de la página principal (sección 1 del sitio).
course_create_sections_if_missing($site, 1);
$portada = str_replace('/course/view.php?id=2', '/course/view.php?id=' . $course->id, file_get_contents("$identidad/portada.html"));
$seccion = $DB->get_record('course_sections', ['course' => $site->id, 'section' => 1]);
course_update_section($site, $seccion, ['name' => '', 'summary' => $portada, 'summaryformat' => FORMAT_HTML]);
paso('Portada');

// 8. Limpiar cachés para ver los cambios.
rebuild_course_cache($site->id, true);
rebuild_course_cache($course->id, true);
theme_reset_all_caches();
purge_all_caches();
paso('Cachés limpias');

echo "Listo. Abre http://localhost:8081\n";
