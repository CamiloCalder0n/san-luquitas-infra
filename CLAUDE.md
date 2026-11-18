# Reglas para Claude Code en este repo

Proyecto: Hackathon InfraTech "San Luquitas Digital" (UNAB, Infraestructura Tecnológica).
Contexto completo: `docs/CONTEXTO_SANLUQUITAS_PARA_CHAT.md`. Guía paso a paso: `docs/guia-paso-a-paso.md`.

## Cómo trabajar

- Hablar en español colombiano, directo, un paso a la vez.
- Seguir la guía que el usuario indique (`docs/guias/...`) en orden. No saltarse pasos ni inventar requisitos.
- **No hacer commit, push, merge ni tag.** Al final de cada paso, proponer el mensaje de commit y el usuario lo ejecuta.
- No poner a Claude como coautor ni mencionar IA en commits o PR.
- Commits en español con prefijo: `feat:`, `fix:`, `infra:`, `docs:`.
- Nunca escribir secretos reales en archivos del repo (`.env` no se sube).
- Si algo no cuadra con el enunciado de la hackathon o con la guía, preguntar antes de improvisar.

## Límites por persona

- **Santiago:** solo `portal-laravel/` (y capturas en `docs/capturas/`). No tocar `compose.yaml`, `docker/` ni `scripts/` sin avisar a Cami.
- **Cami:** servidor, `docker/`, `compose.yaml`, `scripts/`, `moodle/`, `docs/`.

## Datos técnicos que no se cambian

- Portal: Laravel 12, PHP 8.3, MariaDB 11.4 (host `db`), Docker Compose, puerto 8080.
- Las variables de la app vienen del `.env` de la raíz del repo, no del de `portal-laravel/`.
- En vistas no usar `env()`: usar `config('app.moodle_url')`.
- Logs a `stderr` (se ven con `docker compose logs`).
- Todos los datos son ficticios. Nada de datos personales reales ni de menores.

## Estilo de textos para la U

Sencillo, de estudiante, puntual, poco texto, sin adornos ni jerga rebuscada. Que no se note tanta IA.
