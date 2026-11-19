---
name: revision-pr
description: Revisión de código de un PR del repo san-luquitas-infra (portal Laravel, Docker, docs). Revisa el diff contra la guía, las reglas de CLAUDE.md, Laravel 12, compatibilidad con Docker y seguridad; corre las verificaciones; deja un comentario por ronda en el PR. Si hay hallazgos los corrige, hace push y vuelve a revisar desde cero hasta dar luz verde. Usar después de abrir un PR o con "/revision-pr <número>".
metadata:
  short-description: Revisar un PR, corregir y repetir desde cero hasta luz verde
---

# Revisión de PR – San Luquitas

## Cuándo usarla

- Justo después de abrir un PR (es obligatorio en el flujo del repo).
- Cuando el usuario diga `/revision-pr <número>`, "revisa el PR", "corre la revisión".

Recibe un número de PR. Si no viene, usar el PR abierto de la rama actual (`gh pr view --json number`). Si no hay, preguntar.

Repo: `CamiloCalder0n/san-luquitas-infra`.

## Flujo completo (contexto)

1. **Revisión doble antes de entregar** (antes de commit/PR): dos pasadas generales sobre el cambio con el checklist de abajo. Si la segunda encuentra algo, se corrige y se repiten las dos.
2. Commit, push y PR (base `main` o la rama anterior si el PR depende de otro). Reviewer: Cami (`CamiloCalder0n`).
3. **Esta skill**: revisión del PR → si hay hallazgos se corrigen → se revisa **desde cero** otra vez → hasta luz verde.
4. Cami revisa y hace el merge. Claude **nunca** hace merge, tag ni force-push.

## Contrato de salida

Cada ronda termina en uno de estos estados:

| Estado | Qué pasa |
|---|---|
| **Luz verde** | 0 hallazgos medio+ y todas las verificaciones pasan. Comentario final en el PR. Fin. |
| **Con hallazgos** | Comentario de la ronda en el PR → corregir → commit → push → ronda siguiente desde cero. |
| **Escalado** | Se llegó a 5 rondas, o un hallazgo necesita decisión del usuario o de Cami (cambia `compose.yaml`, `docker/`, `scripts/`, `.env.example`, o contradice la guía). Se avisa en terminal y en el PR, y se para. |

## Qué revisar (checklist)

Leer siempre antes: `CLAUDE.md`, `docs/CONTEXTO_SANLUQUITAS_PARA_CHAT.md` y la guía que corresponda en `docs/guias/`.

### 1. Alcance y reglas del equipo
- Santiago solo toca `portal-laravel/`, `docs/capturas/`, `docs/registro-ia.md` (y el flujo de revisión en `.claude/` / `CLAUDE.md` cuando se acordó). Cambios en `compose.yaml`, `docker/`, `scripts/`, `moodle/`, `.env.example` → **escalar a Cami**, no corregir por cuenta propia.
- El PR hace lo que dice el título y nada más (sin cosas que no pide la guía).
- Commits en español con prefijo `feat:`, `fix:`, `infra:`, `docs:`. Sin `Co-Authored-By` ni menciones de IA en commits, título, cuerpo ni comentarios del PR.

### 2. Secretos y archivos que no van
- No se suben `.env`, `vendor/`, `node_modules/`, `public/build/`, `database/*.sqlite`, `bootstrap/cache/*.php`, `storage/logs`.
- Ninguna clave, `APP_KEY`, contraseña o token en código, docs, capturas o mensajes. El repo es **público**.

### 3. Cumple la guía / enunciado
- Comparar contra la guía paso a paso: nombres de rutas (`inicio`, `institucion`, `recursos`, `novedades.*`), tabla `novedades`, campos y tipos, categorías `noticia|comunicado|evento`, paginación de 6 más recientes primero, mensajes de validación y flash en español, 6 novedades ficticias, recursos pedidos, menú y botón "Ir a San Luquitas Digital", pie de página.
- Todos los datos ficticios. Nada de personas reales ni menores identificables.

### 4. Laravel 12
- En vistas **nunca** `env()`: usar `config('app.moodle_url')`. `env()` solo dentro de `config/`.
- Validación en `FormRequest`, `$fillable` en el modelo (sin `$guarded = []`).
- Formularios con `@csrf`; eliminar con `@method('DELETE')` y confirmación.
- Blade con `{{ }}`; `{!! !!}` solo con contenido que no venga del usuario.
- Route model binding con parámetro `novedad` (`->parameters(['novedades' => 'novedad'])`).
- Paginación con `withQueryString()` si hay filtro.
- Nada de closures/código que rompa `config:cache`, `route:cache` o `view:cache` (el contenedor los corre al arrancar).

### 5. Compatibilidad con Docker y el servidor
- `composer.lock` y `package-lock.json` presentes y al día. `composer.json` con `config.platform.php = 8.3.0` (el contenedor es PHP 8.3; nada que pida 8.4+).
- Migraciones que funcionen en **MariaDB 11.4** (no solo SQLite): longitudes de `string`, sin tipos exclusivos de SQLite.
- Logs a `stderr` (no escribir archivos de log propios).
- Si el código necesita una variable nueva → tiene que estar en `.env.example` (eso lo cambia Cami: escalar).

### 6. Seguridad (OWASP básico)
- XSS (salida sin escapar), CSRF, asignación masiva, SQL crudo con datos del usuario, validación de `categoria` con `in:`, `APP_DEBUG` no forzado a `true`, headers o rutas de debug expuestas.
- **Limitación aceptada:** el CRUD de novedades no tiene login (decisión del equipo). No reportarlo como hallazgo; solo verificar que esté anotado en la documentación cuando toque.

### 7. Textos
- Español sencillo, de estudiante, sin jerga rebuscada ni relleno.

## Severidad

Solo se reportan **medio o más**. Lo bajo (gustos de nombres, estilo menor) no se reporta.

| Severidad | Criterio | Ejemplos |
|---|---|---|
| **Crítico** | Secretos, datos reales, algo que rompe el despliegue | `.env` o clave en el diff, la imagen Docker no construye, la app no arranca |
| **Alto** | No cumple la guía o rompe una regla del proyecto | `env()` en una vista, ruta con otro nombre, tabla mal nombrada, falta `@csrf`, validación ausente |
| **Medio** | Calidad que afecta mantenimiento o evaluación | código duplicado, consulta sin paginar, mensaje en inglés, dependencia innecesaria |

Solo revisar lo que el PR cambia. Lo que ya estaba antes no se reporta, salvo que el cambio nuevo dependa de eso y quede roto.

## Pasos de cada ronda

Cada ronda es **desde cero**: diff completo del PR contra su base, checklist completo y todas las verificaciones. No se reutilizan conclusiones de rondas anteriores.

### Paso 1 – Contexto del PR
```bash
gh pr view <n> -R CamiloCalder0n/san-luquitas-infra --json number,title,body,baseRefName,headRefName,files,author
gh pr diff <n> -R CamiloCalder0n/san-luquitas-infra
```
Confirmar que la rama local es `headRefName` y está al día con `origin` (`git status -sb`).

### Paso 2 – Revisión en paralelo (2 agentes, un solo mensaje)
- **Agente A – código y guía** (`general-purpose`): recibe el diff, la lista de archivos, el cuerpo del PR y los puntos 1, 3, 4, 5 y 7 del checklist. Debe leer los archivos completos cuando necesite contexto y la guía correspondiente.
- **Agente B – seguridad y secretos** (`general-purpose`): recibe el diff y los puntos 2 y 6.

A los dos: devolver solo hallazgos medio+, cada uno con severidad, área, `archivo:línea`, resumen de una línea y explicación corta con la corrección concreta. No pasarles mis conclusiones previas (revisión independiente).

### Paso 3 – Verificaciones (las corro yo mientras tanto)
Dentro de `portal-laravel/` (si el PR lo toca):
```bash
composer validate --no-check-publish
php artisan route:list
php artisan migrate:fresh --seed     # SQLite local
php artisan test
npm run build
```
Desde la raíz, si el PR toca `portal-laravel/`, `docker/` o `compose.yaml`:
```bash
rm -f portal-laravel/bootstrap/cache/packages.php portal-laravel/bootstrap/cache/services.php
docker compose up -d --build
docker compose ps
curl -s -o /dev/null -w "%{http_code}\n" http://localhost:8080/   # y las rutas nuevas
docker compose logs --tail=50 app | grep -iE "error|exception" || true
```
Si el puerto 8080 está ocupado por otro contenedor, preguntar al usuario antes de pararlo.

Cada verificación que falle es un hallazgo **crítico** o **alto**.

### Paso 4 – Consolidar
Juntar hallazgos de los agentes y de las verificaciones, quitar duplicados, **comprobar cada uno contra el código real** (descartar falsos positivos con una razón) y ordenar por severidad.

### Paso 5 – Comentario de la ronda (PR + terminal)
Mismo texto en los dos lados. Sin mencionar IA.

Con hallazgos:
```bash
gh pr comment <n> -R CamiloCalder0n/san-luquitas-infra --body "<!-- revision-pr ronda=N -->
### Revisión de código – ronda N: con hallazgos

| # | Severidad | Archivo | Hallazgo |
|---|---|---|---|
| 1 | Alto | \`ruta:línea\` | resumen |

Verificaciones: route:list ✅ · migrate --seed ✅ · test ✅ · build ✅ · docker ❌
Se corrigen y se vuelve a revisar desde cero."
```

Luz verde:
```bash
gh pr comment <n> -R CamiloCalder0n/san-luquitas-infra --body "<!-- revision-pr ronda=N -->
### Revisión de código – ronda N: ✅ luz verde

Sin hallazgos. Revisado: alcance, secretos, guía, Laravel 12, Docker/MariaDB, seguridad.
Verificaciones: route:list ✅ · migrate --seed ✅ · test ✅ · build ✅ · docker ✅ (http://localhost:8080 → 200)
Listo para revisión de @CamiloCalder0n."
```

### Paso 6 – Corregir (si hubo hallazgos)
- Corregir en la misma rama del PR. Leer el código alrededor y arreglar la causa, no el síntoma.
- Hallazgos que tocan archivos de Cami o cambian lo que dice la guía → **no corregir**, escalar.
- Si un hallazgo resulta equivocado, no se corrige: se explica por qué en el siguiente comentario.
- Commit: `fix: <qué se corrigió>` (sin coautoría ni IA). `git push` normal, **nunca** `--force`.
- Si hay PRs que salen de esta rama, avisar al final que quedan para actualizar.

### Paso 7 – Repetir
Volver al Paso 1 con ronda N+1. Máximo **5 rondas**; si la cantidad de hallazgos no baja de una ronda a otra o se llega a 5, escalar al usuario con la lista de lo pendiente.

## Resumen final (terminal)

```text
## Revisión PR #<n> – <título>
| Ronda | Hallazgos | Corregidos | Commit |
|---|---|---|---|
| 1 | 2 | 2 | abc1234 |
| 2 | 0 | – | – |
Estado: LUZ VERDE / ESCALADO (motivo)
Pendiente para Cami: <cosas de su área, o "nada">
```

Si el usuario lo pide, anotar el uso en `docs/registro-ia.md`.
