# Portal Institucional San Luquitas

Portal web de la IE San Luquitas (colegio ficticio) hecho en Laravel 12. Tiene inicio, información de la institución, recursos de programación y novedades con registro y consulta en la base de datos. Todos los datos son ficticios.

## Páginas

| Ruta | Qué muestra |
|---|---|
| `/` | Bienvenida, 3 últimas novedades y accesos a Moodle y Recursos |
| `/institucion` | Presentación, misión, visión y datos generales |
| `/recursos` | Enlaces a Scratch, Code.org, ScratchJr, Blockly Games y CS Unplugged |
| `/novedades` | Lista con filtro por categoría y paginación; crear, editar y eliminar |

El botón "Ir a San Luquitas Digital" usa `MOODLE_URL` (en `config/app.php` como `app.moodle_url`).

## Probar en el PC

```bash
composer install
npm install
cp .env.example .env
php artisan key:generate
touch database/database.sqlite   # base de datos local (SQLite)
php artisan migrate --seed
npm run dev          # en una terminal
php artisan serve    # en otra
```

Pruebas automáticas: `php artisan test`.

## Docker (lo que va al servidor)

Se levanta desde la raíz del repo con `docker compose up -d --build` y abre en el puerto 8080. Las variables salen del `.env` de la raíz, no del de esta carpeta. Para cargar las novedades de ejemplo: `docker compose exec app php artisan db:seed --force`.

## Limitaciones

- Crear, editar y eliminar novedades no pide login. Fue una decisión del equipo para la hackathon; en un portal real esas acciones serían solo para administradores.
