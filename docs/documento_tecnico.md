# Hackathon InfraTech: San Luquitas Digital

**Documento técnico**

| | |
|---|---|
| Equipo (consultora) | [NOMBRE DEL EQUIPO] |
| Integrantes | Juan Camilo Calderón · Santiago Steven Ruiz · Pablo Samuel · Juan Mora |
| Asignatura | Infraestructura Tecnológica |
| Docente | [NOMBRE DEL DOCENTE] |
| Universidad | Universidad Autónoma de Bucaramanga (UNAB) |
| Fecha | Noviembre de 2026 |
| Repositorio | https://github.com/CamiloCalder0n/san-luquitas-infra |

---

## 1. Portada

Ver la tabla de arriba.

## 2. Introducción

La IE San Luquitas es un colegio rural (caso ficticio) con poca conectividad, pocos equipos y sin personal técnico propio. Necesita dos cosas:

1. Un Entorno Virtual de Aprendizaje (EVA) para enseñar programación y pensamiento computacional a niños de cuarto y quinto de primaria.
2. Un portal institucional para publicar novedades, información del colegio, recursos y el acceso al EVA.

Como "consultora" montamos un solo servidor Linux en una máquina virtual que aloja las dos aplicaciones al mismo tiempo:

- **San Luquitas Digital**: Moodle 5.3 LTS instalado directo en Linux (Apache + PHP 8.3 + PostgreSQL 17).
- **Portal Institucional San Luquitas**: Laravel 12 desplegado con Docker Compose (Nginx + PHP-FPM + MariaDB 11.4).

Todo funciona en red local, sin depender de internet para el uso diario, con firewall, persistencia en volúmenes y directorios, y copias de seguridad en un disco aparte con restauración probada.

## 3. Objetivos

**General:** diseñar, implementar y documentar una infraestructura en un servidor Linux que ejecute Moodle y una aplicación Laravel en Docker para la IE San Luquitas, con seguridad básica, persistencia, respaldo y recuperación.

**Específicos:**

1. Instalar y configurar Ubuntu Server 24.04 LTS en una máquina virtual con disco de sistema y disco de respaldos separados.
2. Instalar Moodle 5.3 con Apache, PHP 8.3 y PostgreSQL 17, y configurar el curso "Mis primeros pasos en programación".
3. Desplegar el portal Laravel con Docker Compose, base de datos MariaDB propia, redes internas y volúmenes.
4. Aplicar seguridad básica: firewall, acceso SSH solo con llave, bases de datos no expuestas y credenciales fuera del repositorio.
5. Automatizar copias de seguridad de las dos aplicaciones y demostrar la restauración.
6. Documentar todo de forma reproducible y justificar las decisiones técnicas.

## 4. Análisis de requerimientos

### Funcionales

| Aplicación | Requerimiento |
|---|---|
| Moodle | Sitio "San Luquitas Digital" con curso de 3 unidades, mínimo 3 recursos, una actividad evaluativa y un foro |
| Moodle | Usuarios: administrador, docente y 3 estudiantes con permisos diferentes |
| Moodle | Identidad visual básica (nombre, logo, colores, portada) |
| Portal | Página principal, información institucional, recursos y enlace a Moodle |
| Portal | Módulo de novedades: registrar, consultar, editar y eliminar en base de datos |

### Tecnológicos

| Componente | Requisito | Lo que usamos |
|---|---|---|
| Moodle 5.3 | PHP 8.3 o 8.4, extensiones intl, sodium, zip, gd, xml, etc. | PHP 8.3.6 |
| Moodle 5.3 | PostgreSQL 17+ o MariaDB 11.4+ | PostgreSQL 17 |
| Moodle 5.3 | `max_input_vars` ≥ 5000 | 5000 |
| Laravel 12 | PHP 8.2+ | PHP 8.3 (imagen `php:8.3-fpm-alpine`) |
| Laravel | Docker obligatorio | Docker Engine 29 + Compose |

### De infraestructura (contexto rural)

- Funcionar en red local sin internet permanente.
- Equipo modesto: un solo servidor con 2 vCPU y 4 GB de RAM.
- Poca administración: sistema LTS, scripts de respaldo y despliegue automáticos.
- No perder información: disco aparte para respaldos y restauración probada.

## 5. Arquitectura tecnológica propuesta

Modalidad elegida: **Tradicional + Docker**. Moodle se instala directo en Linux y Laravel con su base de datos corre en contenedores.

[DIAGRAMA: imagen de la arquitectura (exportar el diagrama de `docs/arquitectura.md`)]

### Componentes

| Componente | Tecnología | Dónde corre | Puerto |
|---|---|---|---|
| Sistema operativo | Ubuntu Server 24.04.5 LTS (ARM64) | VM VirtualBox en Mac M4 (2 vCPU, 4 GB RAM) | — |
| Servidor web Moodle | Apache 2.4 + PHP 8.3.6 | Linux | 80 |
| Moodle | 5.3.1+ (rama `MOODLE_503_STABLE`) | `/var/www/moodle` (web en `public/`) | — |
| BD Moodle | PostgreSQL 17 | Linux, solo `127.0.0.1` | 5432 (no expuesto) |
| Portal web | Nginx 1.27 | Contenedor `sl-portal-web` | 8080 → 80 |
| Portal app | Laravel 12 en PHP-FPM 8.3 | Contenedor `sl-portal-app` | 9000 (interno) |
| BD Portal | MariaDB 11.4 | Contenedor `sl-portal-db` | 3306 (interno) |
| Firewall | UFW | Linux | 22, 80, 8080 |
| Respaldos | Scripts bash + cron | Disco 2 en `/backup` | — |

### Red

La VM usa **NAT con reenvío de puertos** de VirtualBox. Primero se probó red en puente por Wi-Fi, pero el Mac no llegaba a la VM (el ping daba *timeout*), así que se cambió a NAT:

| Servicio | Puerto en el Mac | Puerto en la VM |
|---|---|---|
| SSH | 2222 | 22 |
| Moodle | 8081 | 80 |
| Portal | 8080 | 8080 |

Desde el Mac se entra con `localhost`; desde otro equipo de la red, con la IP del Mac.

### Redes Docker

| Red | Tipo | Contenedores |
|---|---|---|
| `frontend` | bridge | web |
| `backend` | bridge **interna** (sin salida a internet) | web, app, db |

Solo Nginx publica un puerto. La app y la base de datos no son accesibles desde fuera.

### Almacenamiento

| Dato | Ubicación | Tipo |
|---|---|---|
| Código Moodle | `/var/www/moodle` | Directorio (git) |
| Archivos Moodle | `/var/moodledata` (fuera de la carpeta web) | Directorio persistente |
| BD Moodle | `/var/lib/postgresql/17` | Directorio persistente |
| BD Portal | volumen `sanluquitas_portal_db` | Volumen Docker |
| Archivos Portal | volumen `sanluquitas_portal_storage` | Volumen Docker |
| Respaldos | `/backup` en `/dev/sdb` (10 GB, ext4) | Disco separado |

Cada aplicación tiene su propia base de datos y su propio usuario: `moodle`/`moodleuser` en PostgreSQL y `portal_sanluquitas`/`portal_user` en MariaDB.

## 6. Tecnologías utilizadas

| Tecnología | Versión | Uso |
|---|---|---|
| VirtualBox | 7.x (Apple Silicon) | Virtualización |
| Ubuntu Server | 24.04.5 LTS ARM64 | Sistema operativo |
| Apache | 2.4 | Servidor web de Moodle |
| PHP | 8.3.6 | Moodle |
| PostgreSQL | 17 (repositorio PGDG) | BD de Moodle |
| Moodle | 5.3.1+ LTS | EVA |
| Docker Engine / Compose | 29.x / plugin v2 | Contenedores del portal |
| Laravel | 12 | Portal institucional |
| Nginx | 1.27 (alpine) | Servidor web del portal |
| MariaDB | 11.4 | BD del portal |
| UFW, fail2ban | Ubuntu 24.04 | Seguridad |
| Git / GitHub | — | Código, PRs y despliegue |

## 7. Justificación de decisiones técnicas

- **Ubuntu 24.04 LTS y no Fedora ni Ubuntu 26.04.** En clase usamos Fedora, pero sus versiones actuales traen PHP 8.5 y PostgreSQL 18, y su soporte dura unos 13 meses. Ubuntu 26.04 también trae PHP 8.5. Moodle 5.3 solo soporta PHP 8.3 y 8.4. Ubuntu 24.04 trae PHP 8.3 de fábrica y tiene soporte hasta 2029, lo que conviene a un colegio sin personal técnico.
- **Moodle 5.3 LTS.** Es la versión de soporte extendido más reciente, así el colegio no tiene que actualizar seguido.
- **PostgreSQL para Moodle.** Es el motor visto en el manual del curso, Moodle lo soporta bien y la versión 17 cumple el mínimo de Moodle 5.3.
- **Moodle directo en Linux.** Es la forma más documentada, usa menos recursos y su cron, logs y respaldo se administran con herramientas normales del sistema.
- **Laravel en Docker.** Lo exige el enunciado. Además, la imagen queda igual en cualquier servidor y no mezcla versiones de PHP con Moodle.
- **MariaDB para el portal.** Es liviana, compatible con Laravel y separa totalmente los datos del portal de los de Moodle.
- **Un solo servidor.** Un colegio rural difícilmente tiene más de un equipo para esto. 4 GB de RAM alcanzan para las dos aplicaciones con pocos usuarios.
- **Disco aparte para respaldos.** Si se daña el disco del sistema, los respaldos siguen en otro disco.

## 8. Instalación y configuración del servidor Linux

### 8.1 Máquina virtual

| Parámetro | Valor |
|---|---|
| Nombre | `sanluquitas-server` |
| RAM / CPU | 4096 MB / 2 |
| Disco 1 | 30 GB (sistema, LVM ampliado a ~27 GB en `/`) |
| Disco 2 | 10 GB (respaldos) |
| Red | NAT con reenvío de puertos 2222→22, 8081→80, 8080→8080 |

En la instalación se eligió Ubuntu Server, disco de 30 GB con LVM, hostname `sanluquitas`, usuario `adsml` y OpenSSH. Al editar LVM se amplió el volumen lógico de `/` para usar todo el disco (por defecto Ubuntu solo usa la mitad).

[CAPTURA: configuración de la VM en VirtualBox con los 2 discos y la red]
[CAPTURA: pantalla de almacenamiento del instalador con `/` de 26.9G]

### 8.2 Acceso SSH con llave

Desde el Mac:

```bash
ssh-keygen -t ed25519 -C "cami-mac"
ssh-copy-id -p 2222 adsml@localhost
ssh -p 2222 adsml@localhost
```

### 8.3 Actualización y paquetes base

```bash
sudo apt update && sudo apt -y upgrade
sudo timedatectl set-timezone America/Bogota
sudo apt -y install curl git unzip htop ufw ca-certificates nano
```

### 8.4 Disco de respaldos

```bash
lsblk
sudo mkfs.ext4 -L backup /dev/sdb
sudo mkdir -p /backup
echo 'LABEL=backup /backup ext4 defaults 0 2' | sudo tee -a /etc/fstab
sudo mount -a
sudo chmod 700 /backup
df -h / /backup
```

[CAPTURA: `lsblk` y `df -h / /backup`]

### 8.5 Firewall

```bash
sudo ufw default deny incoming
sudo ufw default allow outgoing
sudo ufw allow OpenSSH
sudo ufw allow 80/tcp
sudo ufw allow 8080/tcp
sudo ufw --force enable
sudo ufw status verbose
```

[CAPTURA: `sudo ufw status verbose`]

### 8.6 Docker

```bash
sudo install -m 0755 -d /etc/apt/keyrings
sudo curl -fsSL https://download.docker.com/linux/ubuntu/gpg -o /etc/apt/keyrings/docker.asc
sudo chmod a+r /etc/apt/keyrings/docker.asc
echo "deb [arch=$(dpkg --print-architecture) signed-by=/etc/apt/keyrings/docker.asc] https://download.docker.com/linux/ubuntu $(. /etc/os-release && echo "$VERSION_CODENAME") stable" | sudo tee /etc/apt/sources.list.d/docker.list > /dev/null
sudo apt update
sudo apt -y install docker-ce docker-ce-cli containerd.io docker-buildx-plugin docker-compose-plugin
sudo usermod -aG docker adsml
docker run --rm hello-world
```

[CAPTURA: `docker --version` y `hello-world`]

## 9. Implementación de Moodle

### 9.1 PostgreSQL 17

Seguimos los pasos del manual de bases de datos de la materia (servicio, usuario `postgres`, `psql`, creación de usuario y base, permisos y `pg_hba.conf`), con dos cambios a propósito: la base solo escucha en `localhost` y el firewall no se apaga, porque Moodle está en el mismo servidor y nadie de fuera necesita entrar a la base de datos.

```bash
sudo apt -y install postgresql-common
sudo /usr/share/postgresql-common/pgdg/apt.postgresql.org.sh -y
sudo apt -y install postgresql-17
sudo systemctl enable --now postgresql
sudo passwd postgres

read -s -p "Clave para moodleuser: " DBPASS; echo
sudo -u postgres psql -c "CREATE USER moodleuser WITH PASSWORD '$DBPASS';"
sudo -u postgres psql -c "CREATE DATABASE moodle WITH OWNER moodleuser ENCODING 'UTF8' TEMPLATE template0;"
sudo -u postgres psql -c "GRANT ALL PRIVILEGES ON DATABASE moodle TO moodleuser;"
```

En `/etc/postgresql/17/main/pg_hba.conf` se agregó una sola regla (usuario de Moodle, solo su base, solo desde el mismo servidor y con contraseña cifrada):

```
host    moodle    moodleuser    127.0.0.1/32    scram-sha-256
```

```bash
sudo systemctl restart postgresql
PGPASSWORD="$DBPASS" psql -h 127.0.0.1 -d moodle -U moodleuser -c "SELECT current_user, current_database();"
sudo ss -tlnp | grep 5432   # 127.0.0.1:5432
```

Nota: la primera vez pegamos el SQL en bash en vez de dentro de `psql` y no se creó nada. Por eso dejamos la forma con `psql -c`, que no depende de entrar a la consola.

[CAPTURA: `\l` con la base `moodle`, la línea de `pg_hba.conf` y la prueba de conexión]

### 9.2 Apache y PHP 8.3

```bash
sudo apt -y install apache2 libapache2-mod-php8.3 php8.3 php8.3-cli php8.3-pgsql \
  php8.3-intl php8.3-xml php8.3-mbstring php8.3-curl php8.3-zip php8.3-gd \
  php8.3-soap php8.3-bcmath php8.3-readline
```

Ajustes en `/etc/php/8.3/apache2/conf.d/99-moodle.ini` y `/etc/php/8.3/cli/conf.d/99-moodle.ini`: `max_input_vars = 5000`, `memory_limit = 256M`, `upload_max_filesize = 64M`, `post_max_size = 64M`, `date.timezone = America/Bogota`, `expose_php = Off`. En Apache: `ServerTokens Prod` y `ServerSignature Off`.

### 9.3 Código, moodledata y sitio de Apache

```bash
sudo git clone --depth=1 -b MOODLE_503_STABLE https://github.com/moodle/moodle.git /var/www/moodle
sudo mkdir -p /var/moodledata
sudo chown -R www-data:www-data /var/moodledata
sudo chmod 2770 /var/moodledata
```

Desde Moodle 5.1 solo la carpeta `public/` se publica en la web. VirtualHost (`moodle/moodle.conf` del repo):

```apache
<VirtualHost *:80>
    ServerName sanluquitas.local
    ServerAlias *
    DocumentRoot /var/www/moodle/public
    DirectoryIndex index.php
    <Directory /var/www/moodle/public>
        Options -Indexes +FollowSymLinks
        AllowOverride None
        Require all granted
        FallbackResource /r.php
    </Directory>
    ErrorLog ${APACHE_LOG_DIR}/moodle_error.log
    CustomLog ${APACHE_LOG_DIR}/moodle_access.log combined
</VirtualHost>
```

`FallbackResource /r.php` activa el router de Moodle. Sin `DirectoryIndex index.php` la raíz `/` caía en el router y mostraba error 404.

### 9.4 Instalación por consola

```bash
read -s -p "Clave para admin de Moodle: " ADMPASS; echo
sudo chown -R www-data:www-data /var/www/moodle
sudo -u www-data php /var/www/moodle/admin/cli/install.php \
  --lang=es --wwwroot=http://localhost:8081 --dataroot=/var/moodledata \
  --dbtype=pgsql --dbhost=localhost --dbname=moodle --dbuser=moodleuser \
  --dbpass="$DBPASS" --prefix=mdl_ \
  --fullname="San Luquitas Digital" --shortname="SanLuquitas" \
  --adminuser=admin --adminpass="$ADMPASS" --adminemail=admin@sanluquitas.edu.co \
  --agree-license --non-interactive

sudo chown -R root:root /var/www/moodle
sudo chown root:www-data /var/www/moodle/config.php
sudo chmod 640 /var/www/moodle/config.php
sudo sed -i 's|^require_once|$CFG->routerconfigured = true;\nrequire_once|' /var/www/moodle/config.php
echo '* * * * * www-data /usr/bin/php /var/www/moodle/admin/cli/cron.php >/dev/null 2>&1' | sudo tee /etc/cron.d/moodle
```

El código queda de `root` (el servidor web no puede modificarlo) y `config.php` solo lo lee `www-data`.

[CAPTURA: "La instalación se completó exitosamente" y el inicio de sesión en http://localhost:8081]
[CAPTURA: Administración del sitio → Servidor → Entorno]

## 10. Configuración del EVA

Toda la configuración del EVA se hace con un script versionado en el repo, así es reproducible y no depende de hacer clics a mano:

```bash
sudo -u www-data php /opt/san-luquitas-infra/moodle/configurar_moodle.php
```

El script (`moodle/configurar_moodle.php`) se puede correr varias veces sin duplicar nada y hace lo siguiente:

| Qué | Detalle |
|---|---|
| Identidad | Nombre "San Luquitas Digital", logo y logo compacto, color de marca `#2E7D32` y estilos propios del tema Boost (sin fuentes externas) |
| Portada | Bienvenida, botón al curso y al portal, y tarjetas de las 3 unidades. Activa la página principal (`enablemyhome`), que en Moodle 5.3 viene apagada |
| Curso | "Mis primeros pasos en programación" (PROG45), formato por temas, imagen, población y objetivos |
| Unidad 1 | Secuencias y patrones: página, ficha PDF y cuestionario de 5 preguntas |
| Unidad 2 | Algoritmos cotidianos: página, enlace a CS Unplugged y tarea "Mi algoritmo" |
| Unidad 3 | Scratch: página y 2 enlaces |
| General | Foro "Cuéntanos qué aprendiste" |
| Usuarios | `docente1` (profesor) y `estudiante1`, `estudiante2`, `estudiante3` (estudiantes), matriculados en PROG45 desde `moodle/usuarios.csv` |

El contenido completo está en `docs/contenido-eva.md`. Todos los usuarios y datos son ficticios. El sitio exige iniciar sesión (`forcelogin`), lo que protege la información de los niños.

| Rol | Puede |
|---|---|
| Administrador | Configurar todo el sitio |
| Docente | Editar el curso, calificar la tarea y ver resultados del cuestionario |
| Estudiante | Ver recursos, responder el cuestionario, entregar la tarea y escribir en el foro |

[CAPTURA: portada de San Luquitas Digital]
[CAPTURA: curso con las 3 unidades]
[CAPTURA: vista del docente con modo de edición]
[CAPTURA: vista de un estudiante (ventana de incógnito)]

## 11. Implementación de Laravel en Docker

### 11.1 Aplicación

El portal está en `portal-laravel/` (Laravel 12, PHP 8.3). Lo desarrolló Santiago en ramas separadas con Pull Requests.

| Ruta | Contenido |
|---|---|
| `/` | Inicio con las 3 últimas novedades y acceso a Moodle |
| `/institucion` | Presentación, misión, visión y datos (ficticios) |
| `/recursos` | 5 enlaces de programación para niños |
| `/novedades` | CRUD completo con filtro por categoría y paginación de 6 |

Tabla `novedades`: `id`, `titulo` (150), `resumen` (255), `contenido` (texto), `categoria` (noticia, comunicado o evento), `fecha_publicacion` (con índice), `created_at`, `updated_at`. La validación está en `NovedadRequest` con mensajes en español. El enlace a Moodle sale de `MOODLE_URL` (`config('app.moodle_url')`), no está escrito en el código.

El proyecto tiene 13 pruebas automáticas (`php artisan test`): paginación, filtro, creación, validación, edición, eliminación, seeder, menú y páginas.

### 11.2 Docker

- **`docker/Dockerfile`** de varias etapas: `composer` instala dependencias sin las de desarrollo, `node` compila CSS y JS con Vite, la etapa `app` es `php:8.3-fpm-alpine` con `pdo_mysql` y `opcache`, y la etapa `web` es `nginx:1.27-alpine` con solo la carpeta `public/`.
- **`docker/php/entrypoint.sh`**: al arrancar corre las migraciones y genera las cachés de config, rutas y vistas.
- **`compose.yaml`**: servicios `app`, `web` y `db`, redes `frontend` y `backend` (interna), volúmenes `portal_db` y `portal_storage`, `restart: unless-stopped`, `no-new-privileges` y *healthcheck* de MariaDB para que la app arranque cuando la base esté lista.

Imagen vs. contenedor: la imagen es la plantilla de solo lectura que se construye una vez (`sanluquitas/portal-app`); el contenedor es una instancia en ejecución de esa imagen. Si se borra el contenedor, los datos siguen en los volúmenes.

### 11.3 Despliegue

```bash
git clone https://github.com/CamiloCalder0n/san-luquitas-infra.git /opt/san-luquitas-infra
cd /opt/san-luquitas-infra
cp .env.example .env && chmod 600 .env
echo "base64:$(openssl rand -base64 32)"   # se pega en APP_KEY
nano .env                                   # APP_KEY, DB_PASSWORD, DB_ROOT_PASSWORD
./scripts/deploy.sh
docker compose exec app php artisan db:seed --force
```

[CAPTURA: `docker compose ps`, `docker images`, `docker network ls`, `docker volume ls`]
[CAPTURA: portal en http://localhost:8080 (ver `docs/capturas/portal-01-inicio.png`, `portal-02-novedades.png`, `portal-03-crear.png`)]

## 12. Configuración de bases de datos

| | Moodle | Portal |
|---|---|---|
| Motor | PostgreSQL 17 | MariaDB 11.4 |
| Dónde | Linux | Contenedor `sl-portal-db` |
| Base / usuario | `moodle` / `moodleuser` | `portal_sanluquitas` / `portal_user` |
| Acceso | Solo `127.0.0.1`, regla única en `pg_hba.conf` | Solo red Docker `backend`, sin puerto publicado |
| Persistencia | `/var/lib/postgresql/17` | Volumen `sanluquitas_portal_db` |
| Credenciales | En `config.php` (640, root:www-data) | En `.env` (600, fuera del repo) |

Comandos de administración:

```bash
sudo systemctl status postgresql
sudo -u postgres psql -d moodle -c "SELECT id, username FROM mdl_user;"
docker exec -it sl-portal-db sh -c 'mariadb -uroot -p"$MARIADB_ROOT_PASSWORD" "$MARIADB_DATABASE" -e "SELECT id, titulo FROM novedades;"'
```

La matriz comparativa de los motores está en el apartado 15.

## 13. Seguridad, respaldo y recuperación

### 13.1 Seguridad

| Medida | Cómo |
|---|---|
| Firewall | UFW: entrada denegada salvo 22, 80 y 8080 |
| SSH | Solo con llave, root bloqueado, `fail2ban` (`docs/acceso-remoto.md`) |
| Usuarios | Un usuario por persona (`adsml`, `santiago`), sin entrar como root |
| Bases de datos | Ninguna expuesta a la red |
| Docker | Red `backend` interna, `no-new-privileges`, solo Nginx publica puerto |
| Credenciales | `.env` y `config.php` con permisos restringidos; `.env` en `.gitignore` |
| Moodle | Inicio de sesión obligatorio, código de solo lectura para el servidor web |
| Servidor web | Sin versión en cabeceras (`ServerTokens Prod`, `expose_php = Off`, `server_tokens off`) |

### 13.2 Respaldo

`scripts/backup.sh` crea una carpeta con fecha en `/backup` con:

- `moodle_db.dump` (`pg_dump -Fc`), con Moodle en modo mantenimiento mientras se copia.
- `moodledata.tar.gz` (sin cachés ni temporales) y `moodle_config.php`.
- `portal_db.sql` (`mariadb-dump --single-transaction`), `portal_storage.tar.gz` y `portal.env`.

Se conservan 7 días. Se programa con `scripts/cron-sanluquitas` todos los días a las 2:00 a. m.

```bash
sudo cp /opt/san-luquitas-infra/scripts/cron-sanluquitas /etc/cron.d/sanluquitas
sudo /opt/san-luquitas-infra/scripts/backup.sh
sudo ls -lh /backup/
```

### 13.3 Recuperación

```bash
sudo /opt/san-luquitas-infra/scripts/restore-moodle.sh FECHA
sudo /opt/san-luquitas-infra/scripts/restore-portal.sh FECHA
```

Prueba realizada: [PENDIENTE: describir resultado]

1. Se creó la novedad "Prueba backup" y se hizo respaldo.
2. Se borró la novedad y un recurso del curso.
3. Se restauró con los scripts.
4. La novedad y el recurso volvieron.

[CAPTURA: antes, borrado, comando de restauración y después, para Moodle y para el portal]

### 13.4 Mantenimiento

- Actualizaciones de seguridad automáticas (`unattended-upgrades`).
- Revisar cada semana `/var/log/sanluquitas-backup.log`, `/var/log/apache2/moodle_error.log` y `docker compose logs app`.
- Actualizar el portal después de cada PR con `./scripts/deploy.sh`.
- Actualizar Moodle dentro de la rama 5.3 con `git pull` y `admin/cli/upgrade.php`, previo respaldo.

## 14. Pruebas de funcionamiento

| # | Prueba | Procedimiento | Resultado esperado | Resultado |
|---|---|---|---|---|
| 1 | Acceso al servidor | `ssh -p 2222 adsml@localhost` | Entra con llave | Correcto: entra sin pedir clave |
| 2 | Acceso a Moodle | http://localhost:8081 | Carga San Luquitas Digital | Correcto: instalación exitosa e inicio de sesión como admin |
| 3 | Acceso a Laravel | http://localhost:8080 | Carga el portal | [PENDIENTE] |
| 4 | Funcionamiento simultáneo | Ambas apps abiertas + `docker compose ps` + `systemctl status apache2` | Las dos responden | [PENDIENTE] |
| 5 | BD Moodle | Crear y consultar usuarios | Sin errores | [PENDIENTE] |
| 6 | BD Laravel | Crear una novedad y verla | Aparece | [PENDIENTE] (en pruebas automáticas: correcto) |
| 7 | Acceso por roles | Admin, docente y estudiante | Opciones distintas | [PENDIENTE] |
| 8 | Acceso en red local | Otro equipo a `IP_DEL_MAC:8081` y `:8080` tras `cambiar_url.sh` | Abren | [PENDIENTE] |
| 9 | Reinicio de servicios | `sudo reboot` | Todo arranca solo | [PENDIENTE] |
| 10 | Persistencia Docker | `docker compose down` y `up -d` | Las novedades siguen | [PENDIENTE] |
| 11 | Respaldo | `scripts/backup.sh` | Carpeta en `/backup` | [PENDIENTE] |
| 12 | Restauración Moodle | Borrar recurso → `restore-moodle.sh` | El recurso vuelve | [PENDIENTE] |
| 13 | Restauración Portal | Borrar novedad → `restore-portal.sh` | La novedad vuelve | [PENDIENTE] |
| 14 | Puertos | `sudo ss -tlnp` y `sudo ufw status` | Solo 22, 80 y 8080 | Correcto en UFW: solo 22, 80 y 8080 permitidos |
| 15 | SSH seguro | `ssh -p 2222 -o PubkeyAuthentication=no adsml@localhost` | Permission denied | [PENDIENTE] |
| 16 | BD no expuesta | `sudo ss -tlnp \| grep -E '5432\|3306'` | PostgreSQL solo en 127.0.0.1, MariaDB sin puerto | Correcto en PostgreSQL: `127.0.0.1:5432`. MariaDB [PENDIENTE] |

Otras verificaciones hechas:

- Docker: `docker run --rm hello-world` correcto.
- Disco de respaldos: `/dev/sdb` montado en `/backup` (9,8 GB).
- Portal: 13 de 13 pruebas automáticas correctas (`php artisan test`).
- Script del EVA probado en una instalación limpia de Moodle 5.3: crea curso, 3 unidades, 9 recursos y actividades, 5 preguntas y 4 usuarios, y al correrlo otra vez no duplica nada.

[CAPTURA: una por prueba, guardadas en `docs/capturas/` con el número de la prueba]

## 15. Análisis comparativo

Criterios: consumo de recursos, seguridad, facilidad de administración, mantenimiento, escalabilidad, costo y funcionamiento con conectividad limitada.

| Componente | Tecnología seleccionada | Alternativa considerada | Ventajas y limitaciones | Justificación |
|---|---|---|---|---|
| Sistema operativo | Ubuntu Server 24.04 LTS | Fedora Server / Ubuntu 26.04 | Ubuntu 24.04: soporte hasta 2029 y PHP 8.3 incluido; la desventaja es que no lo vimos en clase. Fedora: lo vimos en clase, pero soporte de ~13 meses, PHP 8.5 y PG 18. Ubuntu 26.04: más nuevo, pero trae PHP 8.5 | Moodle 5.3 solo soporta PHP 8.3 y 8.4. Un colegio sin técnico necesita pocas actualizaciones mayores |
| Servidor web | Apache 2.4 (Moodle) y Nginx (portal) | Nginx para todo | Apache: el más documentado para Moodle, módulo PHP directo, `FallbackResource` simple; usa un poco más de memoria. Nginx: más liviano, pero requiere PHP-FPM y reglas extra para el router de Moodle | Para Moodle se prioriza facilidad. En el contenedor del portal Nginx es más liviano y separa web de PHP |
| Base de datos Moodle | PostgreSQL 17 | MariaDB 11.4 | PostgreSQL: robusto, visto en la materia, buen manejo de transacciones; exige el repositorio PGDG porque Ubuntu trae la 16. MariaDB: viene en Ubuntu, pero ya la usa el portal | Se usa lo que el equipo sabe administrar (manual de la materia) y se separan los motores de las dos apps |
| Base de datos Laravel | MariaDB 11.4 | PostgreSQL / SQLite | MariaDB: liviana, imagen oficial con *healthcheck*, estándar en Laravel. SQLite: aún más simple, pero no es un servicio y no sirve para mostrar redes ni volúmenes | Cumple el requisito y aísla el portal de Moodle |
| Despliegue Moodle | Tradicional (Linux) | Moodle en Docker | Tradicional: menos capas, menos RAM, cron y logs del sistema. Docker: más portátil, pero más memoria y una imagen de Moodle no oficial | Con 4 GB de RAM conviene lo más liviano |
| Despliegue Laravel | Docker Compose (3 servicios) | Laravel instalado en Linux | Docker: entorno igual en el PC de desarrollo y en el servidor, no mezcla PHP con Moodle; ocupa algo más de disco. En Linux: menos capas, pero se mezclan dependencias | Obligatorio por el enunciado y facilita el desarrollo en equipo |
| Almacenamiento | Volúmenes Docker + directorios + disco aparte para respaldos | Carpetas montadas (bind mounts) / NAS | Volúmenes: los maneja Docker y no dependen de rutas. Disco aparte: protege los respaldos si falla el sistema. NAS: más seguro, pero cuesta y depende de la red | Bajo costo y suficiente para un colegio pequeño |
| Respaldo | Script diario + cron, `pg_dump`/`mariadb-dump`/`tar`, 7 días, restauración probada | Instantáneas de VirtualBox / respaldo en la nube | Script: copia por aplicación y se puede restaurar por separado. Instantáneas: rápidas, pero pesadas y de toda la VM. Nube: depende de internet | Funciona sin internet; se recomienda copiar `/backup` a un disco USB cada semana |
| Conectividad | Red local con NAT y reenvío de puertos, script `cambiar_url.sh` | Red en puente / acceso por internet | NAT: funcionó en cualquier Wi-Fi y solo expone 3 puertos. Puente: más directo, pero falló con el Wi-Fi. Internet: requiere dominio, HTTPS y buena conexión | El colegio no tiene buena conexión; la red local es lo que sí puede usar |

## 16. Uso ético y adecuado de IA

### Uso de inteligencia artificial en el desarrollo del proyecto

| # | Fecha | Quién | Herramienta | Prompt principal | Resultado obtenido | Validación técnica | Ajustes realizados | Uso final |
|---|---|---|---|---|---|---|---|---|
| 1 | 2026-11-18 | Santiago | Claude Code | "Lee CLAUDE.md, el contexto y la guía 01. Sigue la guía desde el Paso 1, un paso a la vez" | Proyecto Laravel en `portal-laravel/` con zona horaria, idioma y enlace a Moodle | `php artisan --version`, `docker compose up -d --build` y la página en http://localhost:8080 | La guía instalaba Laravel 13: se fijó `laravel/laravel:^12.0`. Se fijó PHP 8.3 en `composer.json` porque se colaban paquetes de Symfony 8 que piden PHP 8.4 | PR #2 |
| 2 | 2026-11-18 | Santiago | Claude Code | "Haz el PR de novedades: Pasos 3 y 4 de la guía, con layout y vistas de novedades" | Modelo, migración, seeder, validación, controlador, vistas y pruebas del CRUD | 10 pruebas automáticas, migraciones en MariaDB dentro de Docker y revisión del PR | El seeder usaba Faker, que no está en la imagen de producción: se cambió a datos fijos. Se limitó el largo del contenido (error 500 en MariaDB). La paginación salía en versión de celular en Docker: se copió la vista al proyecto. La fecha aceptaba años de 5 dígitos: se limitó a 2000–2100 | PR #3 |
| 3 | 2026-11-18 | Santiago | Claude Code | "Haz el PR de vistas: Inicio, Institución y Recursos con el menú completo" | Páginas Inicio, Institución y Recursos y menú | 13 pruebas automáticas y las 6 páginas abiertas en Docker | La prueba del menú no detectaba si se borraba un enlace: se cambió | PR #4 |
| 4 | 2026-11-18 | Santiago | Claude Code | "Crea una skill de revisión de PRs para este proyecto: revisar, corregir y revisar desde cero hasta luz verde" | Skill `revision-pr`, reglas en `CLAUDE.md` y bloqueo de firmas de IA y comandos peligrosos | La skill revisó cada PR con dos revisores (código y seguridad) y pruebas en un worktree limpio | Varias rondas: datos sensibles fuera de comentarios públicos, no tocar ramas ajenas, PRs encadenados | PR #1 |
| 5 | 2026-11-18 | Santiago | Claude Code | "Haz el PR de evidencias con las capturas" | Capturas del portal en Docker y prueba de persistencia | Novedad "Prueba Docker" creada, `docker compose down` y `up -d`, y siguió | La captura 05 tenía un comando escrito a mano: se rehizo con los comandos exactos | PR #5 |
| 6 | 2026-11-17 | Cami | Claude (chat) | "Lee el enunciado completo y dime qué entiendes y qué arquitectura conviene al colegio" | Resumen del reto y arquitectura Tradicional + Docker | Se revisaron los requisitos oficiales de Moodle 5.3 (moodledev.io) | Se descartó Ubuntu 26.04 y Fedora por traer PHP 8.5; se pasó de PostgreSQL 16 a 17 por el mínimo de Moodle 5.3 | Apartados 5, 7 y 15 |
| 7 | 2026-11-17 | Cami | Claude (chat) | "Dame todo el paso a paso para montar el servidor por SSH, siguiendo el manual del profe" | Guía por fases, repo base, `compose.yaml`, Dockerfile y scripts de respaldo | Cada comando se corrió en la VM y se revisó su salida; los scripts pasaron `bash -n` | Usuario real `adsml`; pasos de PostgreSQL adaptados al manual con base solo en localhost | `docs/guia-paso-a-paso.md`, `scripts/` |
| 8 | 2026-11-18 | Cami | Claude (chat) | "No me carga la VM desde el Mac / sale error 404 / el psql no creó la base" | Diagnóstico: red en puente bloqueada por el Wi-Fi, raíz de Moodle en el router, SQL pegado en bash | `ping`, `systemctl status ssh`, `\l` y pruebas en el navegador | Cambio a NAT con reenvío de puertos; `DirectoryIndex index.php`; creación de la base con `psql -c` | Servidor funcionando |
| 9 | 2026-11-18 | Cami | Claude (chat) | "Deja el EVA listo y que cumpla todo" | Script `configurar_moodle.php`, logo, estilos, portada, contenido del curso y usuarios | Se probó en una instalación limpia de Moodle 5.3 y se revisaron las tablas y la portada en el navegador | Se agregó `enablemyhome` (en 5.3 la portada viene apagada) y se cambió la creación de preguntas porque el generador de pruebas de Moodle necesita PHPUnit | Apartado 10 |

### Reflexión

La IA nos ayudó a ir más rápido en lo repetitivo: comandos, plantillas, código base y diagnóstico de errores. Pero no acertó todo a la primera. Propuso versiones que no servían (Ubuntu 26.04 trae un PHP que Moodle 5.3 no soporta), comandos que se pegaron mal y una red que no funcionó con nuestro Wi-Fi. Cada propuesta se verificó corriéndola en la VM, revisando la documentación oficial y con pruebas automáticas en el caso del portal. No pusimos contraseñas reales ni datos de personas en los prompts. Las decisiones finales y la responsabilidad del funcionamiento son del equipo.

## 17. Limitaciones y recomendaciones

**Limitaciones:**

- **OVA en ARM64.** La VM se creó en un Mac M4, así que el `.ova` solo arranca en equipos ARM (Apple Silicon). En un PC Intel o AMD no inicia. Por eso la demostración se hace con la VM corriendo en el Mac.
- **Red.** La red en puente por Wi-Fi no funcionó, así que se usó NAT con reenvío de puertos. Desde otros equipos se entra con la IP del Mac.
- **Dirección fija de Moodle.** Moodle solo responde en su `wwwroot`. Para abrirlo desde otro equipo hay que correr `sudo ./scripts/cambiar_url.sh IP_DEL_MAC`, y `... localhost` para volver.
- **Sin HTTPS.** Es un laboratorio local; las claves viajan sin cifrar dentro de la red.
- **Un solo servidor.** Si se apaga la VM, se caen las dos aplicaciones.
- **Sin correo.** No hay servidor de correo, así que Moodle no envía notificaciones ni recuperación de contraseña.
- **Portal sin autenticación.** El CRUD de novedades no pide inicio de sesión porque el enunciado no lo exige.

**Recomendaciones para el colegio:**

1. Montar el servidor en un mini PC x86 de bajo consumo con UPS, conectado por cable al router del colegio, con IP fija.
2. Agregar HTTPS con certificado propio de la red local y un nombre interno (por ejemplo `aula.sanluquitas.local`).
3. Copiar `/backup` cada semana a un disco USB guardado fuera del salón.
4. Agregar inicio de sesión al portal para que solo el personal publique novedades.
5. Configurar correo cuando haya conectividad, para notificaciones y recuperación de contraseñas.
6. Usar el portal y Moodle por Wi-Fi local en tabletas, sin necesidad de internet.

## 18. Conclusiones

- Se montó un servidor Ubuntu 24.04 que corre Moodle 5.3 y un portal Laravel en Docker al mismo tiempo, sin conflictos de puertos (80 y 8080).
- Elegir versiones con base en los requisitos oficiales fue clave: con Ubuntu 26.04 o Fedora, Moodle 5.3 no habría instalado por la versión de PHP.
- Separar bases de datos, redes y usuarios por aplicación reduce el riesgo: una falla o un acceso indebido en una no afecta la otra.
- La persistencia se logró con volúmenes Docker y directorios propios de Moodle, y los respaldos van a un disco aparte con scripts de restauración.
- Automatizar la configuración (script del EVA, `deploy.sh`, `backup.sh`) hace que la solución sea reproducible y fácil de mantener.
- Aprendimos a diagnosticar problemas reales: red de la VM, router de Moodle, permisos y conexión a la base de datos.

## 19. Referencias

- Moodle. Notas de versión y requisitos de Moodle 5.3: https://moodledev.io/general/releases/5.3
- Moodle. Notas de versión de Moodle 5.2: https://moodledev.io/general/releases/5.2
- Moodle Docs. Configuring the Router: https://docs.moodle.org/503/en/Configuring_the_Router
- Moodle Docs. Step-by-step Installation Guide for Ubuntu: https://docs.moodle.org/503/en/Step-by-step_Installation_Guide_for_Ubuntu
- Moodle Docs. Apache: https://docs.moodle.org/502/en/Apache
- Laravel 12 Documentation: https://laravel.com/docs/12.x
- Docker Docs. Install Docker Engine on Ubuntu: https://docs.docker.com/engine/install/ubuntu/
- Docker Docs. Compose file reference: https://docs.docker.com/reference/compose-file/
- Docker Docs. Volumes: https://docs.docker.com/engine/storage/volumes/
- PostgreSQL Docs. pg_hba.conf: https://www.postgresql.org/docs/17/auth-pg-hba-conf.html
- PostgreSQL Docs. pg_dump: https://www.postgresql.org/docs/17/app-pgdump.html
- PostgreSQL Apt Repository: https://wiki.postgresql.org/wiki/Apt
- MariaDB Docker Official Image: https://hub.docker.com/_/mariadb
- Ubuntu Server Documentation: https://documentation.ubuntu.com/server/
- Ubuntu. UFW: https://documentation.ubuntu.com/server/how-to/security/firewalls/
- Manual de instalación y configuración de bases de datos (V5), material de la asignatura.

## 20. Anexos

| Anexo | Archivo en el repositorio |
|---|---|
| A. Guía paso a paso completa | `docs/guia-paso-a-paso.md` |
| B. Arquitectura y diagrama | `docs/arquitectura.md` |
| C. Acceso remoto y endurecimiento de SSH | `docs/acceso-remoto.md` |
| D. Contenido del EVA | `docs/contenido-eva.md` |
| E. Script de configuración del EVA | `moodle/configurar_moodle.php` |
| F. VirtualHost y PHP de Moodle | `moodle/moodle.conf`, `moodle/php-moodle.ini` |
| G. Docker | `compose.yaml`, `docker/Dockerfile`, `docker/nginx/default.conf`, `docker/php/` |
| H. Scripts | `scripts/deploy.sh`, `backup.sh`, `restore-moodle.sh`, `restore-portal.sh`, `cambiar_url.sh`, `cron-sanluquitas` |
| I. Pruebas | `docs/pruebas.md` y `portal-laravel/tests/` |
| J. Registro de IA | `docs/registro-ia.md` |
| K. Capturas | `docs/capturas/` |

### Instrucciones para importar la máquina virtual

1. VirtualBox (en un equipo ARM / Apple Silicon) → File → Import Appliance → `sanluquitas-server.ova`.
2. Revisar en Settings → Network que el Adaptador 1 esté en NAT con los reenvíos 2222→22, 8081→80 y 8080→8080.
3. Encender y entrar con `adsml` o por SSH: `ssh -p 2222 adsml@localhost`.
4. Abrir http://localhost:8081 (Moodle) y http://localhost:8080 (portal).
5. Si el portal no carga: `cd /opt/san-luquitas-infra && docker compose up -d`.

[CAPTURA: importación del OVA y las dos aplicaciones funcionando]
