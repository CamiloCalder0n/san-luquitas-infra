# Cómo trabajamos en el repo

## Reglas

1. Nadie sube directo a `main`. Todo entra por Pull Request.
2. Cada tarea va en su propia rama.
3. El otro integrante revisa y aprueba el PR antes de hacer merge.
4. Nunca se sube el `.env` ni contraseñas reales.

## Nombres de ramas

| Prefijo | Para qué | Ejemplo |
|---|---|---|
| `feat/` | Algo nuevo | `feat/novedades-crud` |
| `fix/` | Corregir un error | `fix/conexion-bd` |
| `infra/` | Docker, scripts, servidor | `infra/backup-script` |
| `docs/` | Documentación | `docs/matriz-comparativa` |

## Mensajes de commit

Cortos y en español, empezando por el tipo:

```
feat: agrega listado de novedades
fix: corrige puerto de la base de datos
infra: script de backup de moodle
docs: agrega capturas de docker
```

## Flujo de trabajo

```bash
git checkout main
git pull
git checkout -b feat/mi-tarea

# ... cambios ...

git add .
git commit -m "feat: lo que hice"
git push -u origin feat/mi-tarea
```

Luego en GitHub: **Compare & pull request** → llenar la plantilla → asignar al otro como reviewer.

Después del merge:

```bash
git checkout main
git pull
git branch -d feat/mi-tarea
```

## Portal Laravel (Santiago)

### Crear el proyecto (solo la primera vez)

Desde la raíz del repo:

```bash
composer create-project laravel/laravel portal-laravel
```

### Desarrollar en tu PC

Puedes trabajar normal con `php artisan serve` dentro de `portal-laravel/`.

### Antes de abrir el PR: probar con Docker

Desde la raíz del repo (necesita Docker Desktop):

```bash
cp .env.example .env
# generar APP_KEY y pegarla en .env
echo "base64:$(openssl rand -base64 32)"

docker compose up -d --build
docker compose ps
docker compose logs -f app
```

Abrir http://localhost:8080. Si funciona, abrir el PR.

Para borrar todo y probar desde cero (borra también la base de datos local):

```bash
docker compose down -v
```

### Importante

- La base de datos es MariaDB y el host es `db` (nombre del servicio en Docker).
- Las variables de la app se leen del `.env` de la raíz del repo, no del de `portal-laravel/`.
- Las migraciones se ejecutan solas cuando arranca el contenedor.
- El enlace a Moodle se configura en `config/app.php` con `'moodle_url' => env('MOODLE_URL')` y en las vistas se usa `config('app.moodle_url')`. No usar `env()` directo en las vistas: con `config:cache` devuelve vacío.

## Servidor (Cami)

El servidor tiene el repo clonado en `/opt/san-luquitas-infra` por https (el repo es público, no necesita llaves).

Para actualizar el portal después de un merge:

```bash
cd /opt/san-luquitas-infra
./scripts/deploy.sh
```
