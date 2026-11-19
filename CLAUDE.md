# Reglas para Claude Code en este repo

Proyecto: Hackathon InfraTech "San Luquitas Digital" (UNAB, Infraestructura Tecnológica).
Contexto completo: `docs/CONTEXTO_SANLUQUITAS_PARA_CHAT.md`. Guía paso a paso: `docs/guia-paso-a-paso.md`.

## Cómo trabajar

- Hablar en español colombiano, directo, un paso a la vez.
- Seguir la guía que el usuario indique (`docs/guias/...`) en orden. No saltarse pasos ni inventar requisitos.
- **Nunca** firma de IA: ni `Co-Authored-By`, ni "Generated with Claude Code", ni nada parecido en commits, título o cuerpo del PR, ni comentarios. `.claude/settings.json` lo apaga (`attribution` vacío); no quitarlo. La sección "Uso de IA" de la plantilla del PR sí se llena (cuenta para la nota).
- Commits en español con prefijo: `feat:`, `fix:`, `infra:`, `docs:`.
- Nunca escribir secretos reales en archivos del repo (`.env` no se sube).
- Si algo no cuadra con el enunciado de la hackathon o con la guía, preguntar antes de improvisar.

## Flujo de entrega (commits y PRs)

1. **Revisión doble antes de entregar:** antes de commit, dos pasadas generales sobre el cambio con el checklist de `.claude/skills/revision-pr/SKILL.md`. Si algo falla, se corrige y se repiten las dos (máximo 3 vueltas; si sigue fallando, parar y avisar al usuario).
2. Trabajo en automático: al terminar cada bloque del plan (un PR = un bloque, no un PR por cada paso pequeño) Claude hace la revisión doble, commit, push y abre el PR (reviewer: el otro integrante) sin pedir confirmación, y avisa al usuario. Commit y push solo dentro de este flujo y en la rama del PR, siempre con el "Push seguro" de la skill. Nada se trabaja en `main`.
   - Los PRs que dependen de otro salen de la rama anterior (PRs encadenados) y su base es esa rama. La primera línea del cuerpo dice: "Depende de #N. Fusionar #N primero; antes de fusionar este, cambiar su base a `main`." Al empezar cada bloque se revisa si alguna base ya se fusionó (ver skill, "Base del PR ya fusionada").
   - Mientras el PR del flujo (`docs/flujo-revision`) no esté en `main`, toda rama nueva sale de esa rama (o de una que salga de ella), para tener `.claude/` (skill y `settings.json`). Nunca crear ramas de trabajo desde un `main` que no tenga `.claude/settings.json`.
   - En los commits se agregan los archivos por nombre (nunca `git add -A` ni `git add .`).
3. Apenas se abre el PR, correr la skill `revision-pr`. Si hay hallazgos: corregir, push y revisar desde cero hasta luz verde.
4. **El merge lo hace Cami.** Claude nunca hace merge, tag, rebase ni `push --force`, y nunca sube directo a `main`. `.claude/settings.json` bloquea las formas comunes de esos comandos (ayuda parcial, no garantía; la barrera principal es el "Push seguro" de la skill). No quitar esos bloqueos.
5. Al terminar todas las tareas: revisión completa de todos los PRs juntos, buscando fallas, código mal hecho o duplicado entre PRs. Cada falla se corrige en el PR que la introdujo si sigue abierto (Paso 6 de la skill, y se actualizan sus PRs hijos); si ya se fusionó, en un PR nuevo `fix/...` desde `origin/main`. Termina cuando una pasada completa no encuentra nada medio o más (máximo 3 pasadas; si sigue saliendo algo, parar y avisar al usuario).

## Límites por persona

- **Santiago:** solo `portal-laravel/` (y capturas en `docs/capturas/` y su parte de `docs/registro-ia.md`). No tocar `compose.yaml`, `docker/` ni `scripts/` sin avisar a Cami.
- **Cami:** servidor, `docker/`, `compose.yaml`, `scripts/`, `moodle/`, `docs/`.

## Datos técnicos que no se cambian

- Portal: Laravel 12, PHP 8.3, MariaDB 11.4 (host `db`), Docker Compose, puerto 8080.
- Las variables de la app vienen del `.env` de la raíz del repo, no del de `portal-laravel/`.
- En vistas no usar `env()`: usar `config('app.moodle_url')`.
- Logs a `stderr` (se ven con `docker compose logs`).
- Todos los datos son ficticios. Nada de datos personales reales ni de menores.

## Estilo de textos para la U

Sencillo, de estudiante, puntual, poco texto, sin adornos ni jerga rebuscada. Que no se note tanta IA.
