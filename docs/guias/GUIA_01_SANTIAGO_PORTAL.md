# Guía 01 – Santiago: Portal Institucional San Luquitas

Objetivo: dejar el portal Laravel funcionando en Docker y abrir el PR. Todo se trabaja **solo en `portal-laravel/`**.

**Necesitas en tu PC:** Git, Docker Desktop, PHP 8.2+ y Composer, Node 20+.
(Si no tienes PHP/Composer, en el Paso 1 hay una alternativa con Docker.)

## Prompt para Claude Code

En la terminal de VS Code, dentro del repo, abrir `claude` y pegar:

```
Lee CLAUDE.md, docs/CONTEXTO_SANLUQUITAS_PARA_CHAT.md y docs/guias/GUIA_01_SANTIAGO_PORTAL.md.
Sigue la guía desde el Paso 1, un paso a la vez, y espera mi confirmación entre pasos.
Trabaja solo en portal-laravel/. Para commits y PRs sigue el "Flujo de entrega" de CLAUDE.md.
```

---

## Paso 0 – Repo y rama

1. Aceptar la invitación del repo en GitHub (llega al correo).
2. Clonar y crear la rama:

```bash
git clone https://github.com/CamiloCalder0n/san-luquitas-infra.git
cd san-luquitas-infra
git checkout -b feat/portal-base
```

## Paso 1 – Crear el proyecto Laravel

```bash
composer create-project laravel/laravel portal-laravel
```

Sin PHP/Composer en el PC:

```bash
docker run --rm -v "$PWD":/app -w /app composer create-project laravel/laravel portal-laravel
```

Generar el `package-lock.json` (el Dockerfile lo necesita):

```bash
cd portal-laravel
npm install
```

Comprobar: `php artisan --version` muestra Laravel 12.

## Paso 2 – Configuración básica

En `config/app.php`:
- `'timezone' => 'America/Bogota'`
- `'locale' => env('APP_LOCALE', 'es')`
- Agregar: `'moodle_url' => env('MOODLE_URL', 'http://sanluquitas.local'),`

En las vistas el enlace a Moodle siempre es `config('app.moodle_url')`. **Nunca `env()` en las vistas.**

Para trabajar en tu PC sin Docker, el `portal-laravel/.env` que trae Laravel ya usa SQLite. Agregar ahí `MOODLE_URL=http://localhost` para pruebas.

## Paso 3 – Novedades: modelo, migración y datos de prueba

```bash
php artisan make:model Novedad -mfs
```

Migración (`create_novedads_table` → renombrar la tabla a `novedades`):

| Campo | Tipo |
|---|---|
| id | id |
| titulo | string(150) |
| resumen | string(255) |
| contenido | text |
| categoria | string(30): `noticia`, `comunicado`, `evento` |
| fecha_publicacion | date |
| timestamps | |

Modelo `Novedad`:
- `protected $table = 'novedades';`
- `$fillable` con los campos de arriba
- `casts`: `fecha_publicacion` → `date`

Factory + seeder: 6 novedades **ficticias** del colegio (inicio de clases, club de programación con Scratch, entrega de boletines, feria de ciencia, jornada de vacunación de mascotas de la vereda, etc.). Registrar el seeder en `DatabaseSeeder`.

```bash
php artisan migrate:fresh --seed
```

## Paso 4 – Controlador, validación y rutas

```bash
php artisan make:controller NovedadController --resource --model=Novedad
php artisan make:request NovedadRequest
```

- `NovedadRequest`: `titulo` requerido máx. 150, `resumen` requerido máx. 255, `contenido` requerido, `categoria` en noticia/comunicado/evento, `fecha_publicacion` fecha requerida. Mensajes en español.
- Controlador con las 7 acciones (index paginado de 6 en 6, más recientes primero; show; create; store; edit; update; destroy) y mensajes flash ("Novedad creada", etc.).

Rutas en `routes/web.php`:

| Ruta | Nombre | Qué muestra |
|---|---|---|
| `/` | `inicio` | Portada |
| `/institucion` | `institucion` | Información institucional |
| `/recursos` | `recursos` | Recursos de programación |
| `Route::resource('novedades', NovedadController::class)->parameters(['novedades' => 'novedad'])` | `novedades.*` | CRUD |

Comprobar: `php artisan route:list`.

## Paso 5 – Vistas (Blade + Tailwind que ya trae Laravel)

`resources/views/layouts/app.blade.php`:
- Menú: Inicio · Institución · Recursos · Novedades · botón **"Ir a San Luquitas Digital"** → `config('app.moodle_url')`
- Mensajes flash y pie de página ("IE San Luquitas – información ficticia con fines académicos").
- Diseño sencillo, colores tranquilos, que se vea bien en celular.

Vistas:

| Vista | Contenido |
|---|---|
| `inicio` | Bienvenida, 3 últimas novedades, accesos a Moodle y Recursos |
| `institucion` | Presentación, misión, visión, datos generales (sede rural ficticia, jornada, niveles) |
| `recursos` | Tarjetas con enlaces: Scratch, Code.org, ScratchJr, Blockly Games, CS Unplugged |
| `novedades/index` | Lista con filtro por categoría, paginación, botones crear/editar/eliminar |
| `novedades/show` | Detalle |
| `novedades/create` y `edit` | Formulario compartido (`_form`) con errores de validación |

Eliminar con formulario `DELETE` y confirmación.

## Paso 6 – Probar en tu PC

```bash
npm run dev     # en una terminal
php artisan serve   # en otra
```

Revisar en http://127.0.0.1:8000: todas las páginas, crear, editar y eliminar una novedad, y que los errores de validación salgan en español.

## Paso 7 – Probar con Docker (lo que va al servidor)

Desde la **raíz del repo** (no dentro de portal-laravel):

```bash
cd ..
cp .env.example .env
echo "base64:$(openssl rand -base64 32)"
```

En el `.env` de la raíz: pegar la `APP_KEY`, poner `APP_URL=http://localhost:8080` y claves de prueba en `DB_PASSWORD` y `DB_ROOT_PASSWORD`.

```bash
docker compose up -d --build
docker compose ps
docker compose logs --tail=50 app
docker compose exec app php artisan db:seed --force
```

Checklist en http://localhost:8080:
- [ ] Todas las páginas cargan con estilos
- [ ] Crear una novedad "Prueba Docker"
- [ ] `docker compose down` y `docker compose up -d` → la novedad sigue
- [ ] `docker compose logs app` no muestra errores

Si algo falla con el Dockerfile o el compose, **avisar a Cami** con la captura del error; no cambiarlos por tu cuenta.

Para limpiar todo (borra la BD local): `docker compose down -v`.

## Paso 8 – Capturas, registro IA y PR

1. Capturas a `docs/capturas/` con nombres `portal-01-inicio.png`, `portal-02-novedades.png`, `portal-03-crear.png`, `portal-04-docker-ps.png`, `portal-05-persistencia.png`.
2. En `docs/registro-ia.md` anotar el prompt principal de Claude Code, qué generó y qué tuviste que corregir.
3. Commit y PR: los hace Claude Code siguiendo el "Flujo de entrega" de `CLAUDE.md` (revisión doble, PR con la plantilla llena, reviewer: Cami, y luego la skill `revision-pr` hasta luz verde). El portal puede ir en varios PRs pequeños.

Verificar antes del commit que **no** se suben `.env`, `vendor/` ni `node_modules/` (`git status`).

## Paso 9 – Después del merge

Cami lo despliega en el servidor. Cuando te dé acceso por Tailscale, seguir `docs/acceso-remoto.md` para entrar por SSH y ver el portal en `http://sanluquitas.local:8080`.
