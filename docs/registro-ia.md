# Registro de uso de inteligencia artificial

Anotar aquí cada uso importante de IA. No poner contraseñas ni datos reales en los prompts.

| # | Fecha | Quién | Herramienta | Prompt principal | Resultado obtenido | Validación técnica | Ajustes realizados | Uso final |
|---|---|---|---|---|---|---|---|---|
| 1 | 2026-11-18 | Santiago | Claude Code | "Lee CLAUDE.md, el contexto y la guía 01. Sigue la guía desde el Paso 1, un paso a la vez" | Proyecto Laravel en `portal-laravel/` con zona horaria, idioma y enlace a Moodle | `php artisan --version`, `docker compose up -d --build` y la página en http://localhost:8080 | El comando de la guía instalaba Laravel 13: se fijó `laravel/laravel:^12.0`. Se fijó PHP 8.3 en `composer.json` porque se colaban paquetes de Symfony 8 que piden PHP 8.4 | PR #2 |
| 2 | 2026-11-18 | Santiago | Claude Code | "Haz el PR de novedades: Pasos 3 y 4 de la guía, con layout y vistas de novedades" | Modelo, migración, seeder, validación, controlador, vistas y pruebas del CRUD | 9 pruebas automáticas, migraciones en MariaDB dentro de Docker y revisión del PR | El seeder usaba Faker, que no está en la imagen de producción: se cambió a datos fijos. Se limitó el largo del contenido (MariaDB daba error 500 con textos muy largos). La paginación salía en versión de celular en Docker: se copió la vista de paginación al proyecto | PR #3 |
| 3 | 2026-11-18 | Santiago | Claude Code | "Haz el PR de vistas: Inicio, Institución y Recursos con el menú completo" | Páginas Inicio, Institución y Recursos y menú del layout | 12 pruebas automáticas y las 6 páginas abiertas en Docker | La prueba del menú no detectaba si se borraba un enlace: se cambió para revisar el menú en una página sin enlaces propios | PR #4 |
| 4 | 2026-11-18 | Santiago | Claude Code | "Crea una skill de revisión de PRs basada en otra, pero para este proyecto: revisar, corregir y revisar desde cero hasta luz verde" | Skill `revision-pr`, reglas de trabajo en `CLAUDE.md` y bloqueo de firmas de IA y comandos peligrosos | La misma skill revisó cada PR con dos revisores (código y seguridad) y las pruebas en un worktree limpio | Se ajustó en varias rondas: datos sensibles fuera de los comentarios públicos, no tocar ramas de otra persona y PRs encadenados | PR #1 |
| 5 | 2026-11-18 | Santiago | Claude Code | "Haz el PR de evidencias con las capturas" | Capturas del portal en Docker y prueba de persistencia | Se creó "Prueba Docker" enviando el formulario con `curl` (con su token CSRF), luego `docker compose down` y `up -d`, y la novedad siguió | Una primera versión de la captura 05 tenía un comando escrito a mano y salida recortada; se rehizo con los comandos exactos y la salida completa. Las capturas 04 y 05 son esa salida dibujada como terminal | PR #5 |

## Reflexión

(Escribir al final: para qué sirvió la IA, qué errores tuvo y cómo se verificó lo que propuso.)
