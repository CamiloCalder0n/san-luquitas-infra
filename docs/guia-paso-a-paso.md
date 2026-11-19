# Guía paso a paso – San Luquitas Digital

Orden de trabajo completo, de cero a la sustentación. Cada fase dice **quién** la hace.

| Fase | Qué | Quién |
|---|---|---|
| 0 | Repo en GitHub | Cami |
| 1 | Crear la VM e instalar Ubuntu | Cami |
| 2 | Servidor base (SSH, disco backup, firewall, Docker) | Cami |
| 3 | Acceso remoto para Santiago (Tailscale) | Cami + Santiago |
| 4 | Moodle 5.3 (Apache + PHP 8.3 + PostgreSQL 17) | Cami |
| 5 | Portal Laravel (código) | Santiago |
| 6 | Desplegar el portal en el servidor | Cami |
| 7 | Backups y restauración | Cami |
| 8 | Identidad, curso y usuarios del EVA (script) | Cami (Pablo y Juan revisan) |
| 9 | Pruebas, exportar OVA y documento | Todos |

Las fases 4 (Cami) y 5 (Santiago) se hacen **al mismo tiempo**.

Reemplazar siempre lo que está en MAYÚSCULAS (`TU_USUARIO`, `IP_DEL_MAC`, etc.).

Datos reales del servidor: usuario `adsml`, hostname `sanluquitas`, Ubuntu Server 24.04.5 LTS ARM64, VM en el Mac M4 de Cami.

---

## Fase 0 – Repo en GitHub (Cami)

1. GitHub → **New repository** → nombre `san-luquitas-infra` → **Public** (no lleva secretos; así el servidor lo clona sin llaves) → sin README (ya viene en el zip).
2. En el Mac:

```bash
cd ~/Documents
unzip ~/Downloads/san-luquitas-infra.zip
cd san-luquitas-infra
git init -b main
git add .
git commit -m "infra: estructura base del proyecto"
git remote add origin https://github.com/TU_USUARIO/san-luquitas-infra.git
git push -u origin main
```

3. Settings → **Collaborators** → invitar a Santiago.
4. (Opcional) Settings → **Branches** → regla para `main`: requerir pull request.

---

## Fase 1 – Crear la VM (Cami, en el Mac)

### 1.1 Descargas
- VirtualBox: https://www.virtualbox.org/wiki/Downloads → **macOS / Apple Silicon hosts**
- Ubuntu Server 24.04 LTS ARM: https://cdimage.ubuntu.com/releases/24.04/release/ → `ubuntu-24.04.5-live-server-arm64.iso` (no la 26.04: trae PHP 8.5, que Moodle 5.3 no soporta)

### 1.2 Crear la máquina
VirtualBox → **New**:

| Campo | Valor |
|---|---|
| Name | `sanluquitas-server` |
| ISO Image | el .iso descargado |
| Skip Unattended Installation | ✅ |
| Base Memory | 4096 MB |
| Processors | 2 |
| Hard Disk | Create → 30 GB |

Antes de encenderla, **Settings**:
- **Storage** → en el controlador del disco → agregar disco → **Create** → 10 GB (disco de backups).
- **Network** → Adapter 1 → **NAT** → **Advanced → Port Forwarding** → agregar (dejar las IP vacías):

| Name | Protocol | Host Port | Guest Port |
|---|---|---|---|
| ssh | TCP | 2222 | 22 |
| moodle | TCP | 8081 | 80 |
| portal | TCP | 8080 | 8080 |

Primero se probó **Bridged Adapter** (`en0: Wi-Fi`), pero el Mac no llegaba a la VM (`ping` con timeout). Con NAT + reenvío de puertos funciona siempre, sin depender del router.

Para apagar la VM usar `sudo poweroff` dentro de ella (cerrarla con la X la deja en "Saved" y no toma cambios de red).

### 1.3 Instalar Ubuntu

| Pantalla | Opción |
|---|---|
| Language | English |
| Keyboard | Spanish (Latin American) |
| Type of install | Ubuntu Server |
| Network | DHCP (con NAT sale `10.0.2.15`) |
| Storage | Use an entire disk → **el de 30 GB** (LVM). Editar `ubuntu-lv` y ponerle todo el tamaño (~27 GB), si no Ubuntu usa solo la mitad |
| Profile | Name: `Administrador San Luquitas` · Server: `sanluquitas` · User: `adsml` |
| Ubuntu Pro | Skip |
| SSH | ✅ Install OpenSSH server |
| Featured snaps | Ninguno |

Al terminar → **Reboot Now** → Enter si pide retirar el ISO. Entrar con `adsml` y revisar red y discos:

```bash
ip -br a
lsblk
```

`sda` (30G) tiene el sistema y `sdb` (10G) debe salir vacío.

📸 Capturas: configuración de la VM (RAM, CPU, 2 discos, tabla de Port Forwarding) y pantallas de instalación.

---

## Fase 2 – Servidor base (Cami, por SSH desde el Mac)

### 2.1 Llave SSH desde el Mac

En la VM, si SSH sale `inactive`:

```bash
sudo systemctl enable --now ssh
```

En el Mac (un comando a la vez):

```bash
ssh-keygen -t ed25519 -C "cami-mac"
ssh-copy-id -p 2222 adsml@localhost
ssh -p 2222 adsml@localhost
```

Debe entrar sin pedir clave. Desde aquí todo se hace por SSH.

### 2.2 Actualizar y paquetes base

```bash
sudo apt update && sudo apt -y upgrade
sudo timedatectl set-timezone America/Bogota
sudo apt -y install curl git unzip htop ufw ca-certificates nano
```

No actualizar a la 26.04 aunque el aviso de login lo ofrezca (`do-release-upgrade`).

Actualizaciones de seguridad automáticas (vienen activas, solo verificar):

```bash
systemctl status unattended-upgrades --no-pager
```

### 2.3 Disco de backups

```bash
lsblk
```

Identificar el disco de 10G sin particiones (normalmente `sdb`; si es otro, cambiarlo abajo):

```bash
sudo mkfs.ext4 -L backup /dev/sdb
sudo mkdir -p /backup
echo 'LABEL=backup /backup ext4 defaults 0 2' | sudo tee -a /etc/fstab
sudo mount -a
sudo chmod 700 /backup
df -h / /backup
```

### 2.4 Firewall

```bash
sudo ufw default deny incoming
sudo ufw default allow outgoing
sudo ufw allow OpenSSH
sudo ufw allow 80/tcp
sudo ufw allow 8080/tcp
sudo ufw --force enable
sudo ufw status verbose
```

### 2.5 Docker

```bash
sudo install -m 0755 -d /etc/apt/keyrings
sudo curl -fsSL https://download.docker.com/linux/ubuntu/gpg -o /etc/apt/keyrings/docker.asc
sudo chmod a+r /etc/apt/keyrings/docker.asc
echo "deb [arch=$(dpkg --print-architecture) signed-by=/etc/apt/keyrings/docker.asc] https://download.docker.com/linux/ubuntu $(. /etc/os-release && echo "$VERSION_CODENAME") stable" | sudo tee /etc/apt/sources.list.d/docker.list > /dev/null
sudo apt update
sudo apt -y install docker-ce docker-ce-cli containerd.io docker-buildx-plugin docker-compose-plugin
sudo usermod -aG docker adsml
exit
```

Volver a entrar y probar:

```bash
ssh -p 2222 adsml@localhost
docker --version
docker compose version
docker run --rm hello-world
```

📸 Capturas: `ip -br a`, `lsblk`, `df -h / /backup`, `ufw status verbose`, `docker run hello-world`.

---

## Fase 3 – Acceso remoto para Santiago

Detalle completo en [`acceso-remoto.md`](acceso-remoto.md). Resumen:

**Cami (servidor):**

```bash
curl -fsSL https://tailscale.com/install.sh | sh
sudo tailscale up --hostname sanluquitas
tailscale ip -4
sudo ufw allow in on tailscale0
```

Panel de Tailscale → Machines → `sanluquitas` → Share → mandar el link a Santiago.

**Santiago (su PC):** instalar Tailscale, aceptar el link, crear llave y mandar la pública:

```bash
ssh-keygen -t ed25519 -C "santiago-sanluquitas"
cat ~/.ssh/id_ed25519.pub
```

**Cami (servidor):**

```bash
sudo adduser --gecos "Santiago Steven Ruiz" santiago
sudo usermod -aG sudo,docker santiago
sudo mkdir -p /home/santiago/.ssh
echo "LLAVE_PUBLICA_DE_SANTIAGO" | sudo tee /home/santiago/.ssh/authorized_keys
sudo chown -R santiago:santiago /home/santiago/.ssh
sudo chmod 700 /home/santiago/.ssh
sudo chmod 600 /home/santiago/.ssh/authorized_keys

sudo groupadd devs
sudo usermod -aG devs adsml
sudo usermod -aG devs santiago
sudo mkdir -p /opt/san-luquitas-infra
sudo chown adsml:devs /opt/san-luquitas-infra
sudo chmod 2775 /opt/san-luquitas-infra
```

Blindar SSH (dejar una sesión abierta mientras se prueba):

```bash
sudo tee /etc/ssh/sshd_config.d/01-sanluquitas.conf > /dev/null <<'EOF'
PermitRootLogin no
PasswordAuthentication no
KbdInteractiveAuthentication no
PubkeyAuthentication yes
AllowUsers adsml santiago
MaxAuthTries 3
LoginGraceTime 30
EOF
sudo sshd -t && sudo systemctl restart ssh
sudo apt -y install fail2ban
sudo systemctl enable --now fail2ban
```

**Santiago:** `ssh santiago@100.X.Y.Z`. Tailscale va dentro de la VM y funciona aunque la red sea NAT (solo necesita salida a internet).

Para ver Moodle y el portal desde su navegador (Moodle solo acepta `http://localhost:8081`):

```bash
ssh -N -L 8081:localhost:80 -L 8080:localhost:8080 santiago@100.X.Y.Z
```

---

## Fase 4 – Moodle 5.3 LTS (Cami)

Moodle 5.3 pide PHP 8.3+, PostgreSQL 17+ y `max_input_vars` ≥ 5000.

### 4.1 PostgreSQL 17 (repositorio oficial)

```bash
sudo apt -y install postgresql-common
sudo /usr/share/postgresql-common/pgdg/apt.postgresql.org.sh -y
sudo apt -y install postgresql-17
sudo systemctl enable --now postgresql
psql --version
```

Mismos pasos del manual de bases de datos visto en clase (servicio, usuario postgres, psql, usuarios y permisos), adaptados a Ubuntu y **sin apagar el firewall**.

Estado del servicio:

```bash
sudo systemctl status postgresql --no-pager
```

Contraseña del usuario `postgres` del sistema (paso 4 del manual):

```bash
sudo passwd postgres
```

Usuario y base de datos de Moodle. Se hace con `psql -c` (sin entrar a la consola de `psql`): la primera vez se pegaron los SQL en bash y salió `ALTER: command not found`. La primera línea pide la clave de `moodleuser` (no se ve al escribir; anotarla). **No cerrar la Terminal** hasta terminar la 4.5, porque `$DBPASS` se usa en el instalador:

```bash
read -s -p "Clave para moodleuser: " DBPASS; echo
sudo -u postgres psql -c "CREATE USER moodleuser WITH PASSWORD '$DBPASS';"
sudo -u postgres psql -c "CREATE DATABASE moodle WITH OWNER moodleuser ENCODING 'UTF8' TEMPLATE template0;"
sudo -u postgres psql -c "GRANT ALL PRIVILEGES ON DATABASE moodle TO moodleuser;"
sudo -u postgres psql -c "\l moodle"
sudo -u postgres psql -c "\du"
```

Si sale `(END)`, presionar `q`.

Configuración (en Ubuntu los archivos están en `/etc/postgresql/17/main/`):

```bash
sudo nano /etc/postgresql/17/main/postgresql.conf
```

Dejar:

```
listen_addresses = 'localhost'
port = 5432
```

A diferencia del manual (`'*'`), aquí se deja en `localhost` (es el valor por defecto en Ubuntu) porque Moodle está en el mismo servidor y nadie de afuera necesita entrar a la base de datos. Tampoco se apaga el firewall como en el manual.

```bash
sudo nano /etc/postgresql/17/main/pg_hba.conf
```

Debajo de `# IPv4 local connections:` dejar:

```
# TIPO  BASE    USUARIO     DIRECCION       METODO
host    moodle  moodleuser  127.0.0.1/32    scram-sha-256
```

Solo el usuario de Moodle, solo a su base de datos, solo desde el mismo servidor y con contraseña cifrada.

```bash
sudo systemctl restart postgresql
PGPASSWORD="$DBPASS" psql -h 127.0.0.1 -d moodle -U moodleuser -c "SELECT current_user, current_database();"
sudo ss -tlnp | grep 5432
```

Debe salir `moodleuser | moodle` y `127.0.0.1:5432` (no `0.0.0.0`).

### 4.2 Apache + PHP 8.3

```bash
sudo apt -y install apache2 libapache2-mod-php8.3 php8.3 php8.3-cli php8.3-pgsql \
  php8.3-intl php8.3-xml php8.3-mbstring php8.3-curl php8.3-zip php8.3-gd \
  php8.3-soap php8.3-bcmath php8.3-readline
php -v
php -m | grep -E "sodium|intl|pgsql|zip|gd|soap"
```

Debe salir PHP 8.3.x (en el servidor quedó 8.3.6).

Ajustes de PHP y seguridad de Apache (desde el repo, ver fase 6.1 si aún no está clonado; se puede copiar el contenido a mano):

```bash
sudo tee /etc/php/8.3/apache2/conf.d/99-moodle.ini /etc/php/8.3/cli/conf.d/99-moodle.ini > /dev/null <<'EOF'
max_input_vars = 5000
memory_limit = 256M
upload_max_filesize = 64M
post_max_size = 64M
max_execution_time = 300
date.timezone = America/Bogota
expose_php = Off
opcache.enable = 1
opcache.memory_consumption = 128
opcache.max_accelerated_files = 10000
EOF

sudo sed -i 's/^ServerTokens .*/ServerTokens Prod/; s/^ServerSignature .*/ServerSignature Off/' /etc/apache2/conf-available/security.conf
```

### 4.3 Código de Moodle y moodledata

```bash
sudo git clone --depth=1 -b MOODLE_503_STABLE https://github.com/moodle/moodle.git /var/www/moodle
sudo mkdir -p /var/moodledata
sudo chown -R www-data:www-data /var/moodledata
sudo chmod 2770 /var/moodledata
```

`moodledata` queda fuera de la carpeta web, como recomienda Moodle.

### 4.4 VirtualHost

```bash
sudo tee /etc/apache2/sites-available/moodle.conf > /dev/null <<'EOF'
<VirtualHost *:80>
    ServerName localhost
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
EOF

sudo a2dissite 000-default
sudo a2ensite moodle
sudo apache2ctl configtest
sudo systemctl restart apache2
```

`DirectoryIndex index.php` es necesario: sin esa línea, con `FallbackResource /r.php`, la página de inicio (`/`) salía **Error 404** de Moodle. Debe decir `Syntax OK` (el aviso `AH00558` no es error).

### 4.5 Instalar Moodle por consola

El instalador necesita escribir `config.php`, así que por un momento la carpeta es de `www-data`. La primera línea pide la clave del **admin** de Moodle (mínimo 8 caracteres, con mayúscula, minúscula, número y símbolo; anotarla):

```bash
read -s -p "Clave para admin de Moodle: " ADMPASS; echo
sudo chown -R www-data:www-data /var/www/moodle

sudo -u www-data php /var/www/moodle/admin/cli/install.php \
  --lang=es \
  --wwwroot=http://localhost:8081 \
  --dataroot=/var/moodledata \
  --dbtype=pgsql \
  --dbhost=localhost \
  --dbname=moodle \
  --dbuser=moodleuser \
  --dbpass="$DBPASS" \
  --prefix=mdl_ \
  --fullname="San Luquitas Digital" \
  --shortname="SanLuquitas" \
  --summary="Entorno Virtual de Aprendizaje de la IE San Luquitas" \
  --adminuser=admin \
  --adminpass="$ADMPASS" \
  --adminemail=admin@sanluquitas.edu.co \
  --agree-license \
  --non-interactive
```

Tarda varios minutos y termina con `La instalación se completó exitosamente`. Se instaló Moodle **5.3.1+**. El `wwwroot` es `http://localhost:8081` porque se entra por el reenvío de puertos del Mac. Al terminar, devolver la carpeta a root y proteger `config.php`:

```bash
sudo chown -R root:root /var/www/moodle
sudo chown root:www-data /var/www/moodle/config.php
sudo chmod 640 /var/www/moodle/config.php
```

Avisar a Moodle que el router ya está configurado en Apache (la línea `FallbackResource`):

```bash
sudo sed -i 's|^require_once|$CFG->routerconfigured = true;\nrequire_once|' /var/www/moodle/config.php
sudo grep -n "routerconfigured\|require_once" /var/www/moodle/config.php
```

Debe salir `$CFG->routerconfigured = true;` justo antes de `require_once`.

### 4.6 Cron de Moodle

```bash
echo '* * * * * www-data /usr/bin/php /var/www/moodle/admin/cli/cron.php >/dev/null 2>&1' | sudo tee /etc/cron.d/moodle
sudo -u www-data php /var/www/moodle/admin/cli/cron.php | tail -n 5
```

(En la fase 7 este archivo se reemplaza por el del repo, que incluye también el backup.)

### 4.7 Verificar

- Abrir http://localhost:8081 en el Mac y entrar como `admin`.
- Administración del sitio → Servidor → **Entorno**: todo en verde. Si sale aviso del router, revisar `FallbackResource` en el VirtualHost y `routerconfigured` en `config.php`. Los avisos de HTTPS se ignoran (laboratorio local, va en limitaciones).
- Administración del sitio → Notificaciones: sin errores.

📸 Capturas: `psql --version`, `php -v`, `apache2ctl configtest`, pantalla de Entorno, página de inicio.

---

## Fase 5 – Portal Laravel (Santiago)

Ver también [`../CONTRIBUTING.md`](../CONTRIBUTING.md).

### 5.1 Crear el proyecto

```bash
git clone https://github.com/CamiloCalder0n/san-luquitas-infra.git
cd san-luquitas-infra
git checkout -b feat/portal-base
composer create-project laravel/laravel portal-laravel
```

### 5.2 Lo que debe tener el portal

| Página | Ruta | Contenido |
|---|---|---|
| Inicio | `/` | Presentación de la IE San Luquitas (ficticia), últimas 3 novedades, botón a Moodle |
| Institución | `/institucion` | Misión, visión, datos generales (ficticios) |
| Recursos | `/recursos` | Enlaces de programación para niños (Scratch, Code.org, etc.) |
| Novedades | `/novedades` | Listado + crear, editar, eliminar (CRUD) |
| Moodle | botón en el menú | `config('app.moodle_url')` |

Modelo sugerido:

```bash
cd portal-laravel
php artisan make:model Novedad -mcr
```

Migración `novedades`: `id`, `titulo` (string), `contenido` (text), `categoria` (string: noticia/comunicado), `fecha_publicacion` (date), `timestamps`.

En el modelo: `protected $table = 'novedades';` y `protected $fillable = ['titulo','contenido','categoria','fecha_publicacion'];`

Para el enlace a Moodle, en `config/app.php` agregar `'moodle_url' => env('MOODLE_URL', 'http://localhost:8081'),` y en las vistas usar `config('app.moodle_url')` (con `config:cache`, `env()` no funciona fuera de los archivos de config).

### 5.3 Probar con Docker antes del PR

Desde la raíz del repo:

```bash
cp .env.example .env
echo "base64:$(openssl rand -base64 32)"   # pegar en APP_KEY del .env
# en .env local cambiar APP_URL=http://localhost:8080 y MOODLE_URL a lo que quieran
docker compose up -d --build
docker compose ps
docker compose logs -f app
```

Abrir http://localhost:8080, crear una novedad, luego:

```bash
docker compose down
docker compose up -d
```

La novedad debe seguir ahí. Si todo funciona → commit, push y PR.

```bash
git add .
git commit -m "feat: portal base con novedades"
git push -u origin feat/portal-base
```

Asegurarse de que `portal-laravel/composer.lock` y `portal-laravel/package-lock.json` se suban (el Dockerfile los usa).

---

## Fase 6 – Desplegar el portal en el servidor (Cami)

### 6.1 Clonar el repo en el servidor

El repo es público, así que se clona por https (no hace falta deploy key ni llaves):

```bash
sudo git clone https://github.com/CamiloCalder0n/san-luquitas-infra.git /opt/san-luquitas-infra
sudo chown -R adsml:adsml /opt/san-luquitas-infra
git config --global --add safe.directory /opt/san-luquitas-infra
```

### 6.2 Configurar y levantar

```bash
cd /opt/san-luquitas-infra
cp .env.example .env
chmod 600 .env
echo "base64:$(openssl rand -base64 32)"
nano .env
```

En `.env` cambiar: `APP_KEY`, `DB_PASSWORD`, `DB_ROOT_PASSWORD`. Dejar `APP_URL=http://localhost:8080` y `MOODLE_URL=http://localhost:8081`. Guardar con Ctrl+O, Enter, Ctrl+X.

```bash
chmod +x scripts/*.sh
./scripts/deploy.sh
```

La primera vez tarda (descarga imágenes y compila). Cargar las novedades de ejemplo (solo la primera vez):

```bash
docker compose exec app php artisan db:seed --force
```

Verificar:

```bash
docker compose ps
docker compose logs --tail=50 app
docker images
docker network ls
docker volume ls
```

Abrir http://localhost:8080 en el Mac.

### 6.3 Actualizar después de cada PR

```bash
cd /opt/san-luquitas-infra
./scripts/deploy.sh
```

📸 Capturas: `docker compose ps`, `docker images`, `docker network ls`, `docker volume ls`, el portal funcionando.

---

## Fase 7 – Backups y restauración (Cami)

### 7.1 Programar tareas

```bash
sudo cp /opt/san-luquitas-infra/scripts/cron-sanluquitas /etc/cron.d/sanluquitas
sudo chmod 644 /etc/cron.d/sanluquitas
sudo rm -f /etc/cron.d/moodle
```

Ahora el cron de Moodle y el backup diario (2:00 a.m.) quedan en un solo archivo.

### 7.2 Backup manual

```bash
sudo /opt/san-luquitas-infra/scripts/backup.sh
sudo ls -lh /backup/
```

### 7.3 Prueba de restauración (obligatoria)

**Portal:**
1. Crear una novedad "Prueba backup" → hacer backup → anotar la carpeta (ej. `2026-11-20_1530`).
2. Borrar esa novedad desde el portal.
3. Restaurar:

```bash
sudo /opt/san-luquitas-infra/scripts/restore-portal.sh 2026-11-20_1530
```

4. La novedad vuelve a aparecer.

**Moodle:**
1. Con el curso creado → hacer backup.
2. Borrar un recurso del curso (por ejemplo una página).
3. Restaurar:

```bash
sudo /opt/san-luquitas-infra/scripts/restore-moodle.sh 2026-11-20_1530
```

4. El recurso vuelve a aparecer.

📸 Capturas de cada paso (antes, borrado, comando, después). Esto es lo que más pesa en la rúbrica de seguridad y respaldo.

---

## Fase 8 – Identidad, curso y usuarios del EVA (Cami)

Todo se configura con un solo script del repo (configuración reproducible, queda guardada en la base de datos y entra en los backups):

```bash
sudo -u www-data php /opt/san-luquitas-infra/moodle/configurar_moodle.php
```

Qué deja listo:

| Parte | Qué hace |
|---|---|
| Identidad | Nombre y nombre corto "San Luquitas Digital", resumen, logo y logo compacto (`moodle/identidad/`), color de marca `#2E7D32` y SCSS propio en el tema Boost, página de inicio por defecto = Página principal |
| Portada | Bienvenida con botones "Entrar al curso" y "Portal institucional" y tarjetas de las 3 unidades (`moodle/identidad/portada.html`) |
| Curso | "Mis primeros pasos en programación" (`PROG45`), descripción con población y objetivos, imagen del curso, 3 unidades |
| Recursos | Páginas por unidad, archivo `ficha_patrones.pdf`, URLs (CS Unplugged, Scratch, Ideas de Scratch) |
| Actividades | Cuestionario de 5 preguntas (Unidad 1), Tarea "Mi algoritmo" (Unidad 2), Foro "Cuéntanos qué aprendiste" |
| Usuarios | `docente1` (profesor) y `estudiante1`, `estudiante2`, `estudiante3` (estudiantes), matriculados en PROG45 (`moodle/usuarios.csv`) |

Textos del curso: [`contenido-eva.md`](contenido-eva.md).

Revisión manual (Pablo y Juan):
- [ ] http://localhost:8081 muestra la portada verde con el logo.
- [ ] El curso tiene las 3 unidades con sus recursos y actividades.
- [ ] El cuestionario tiene las 5 preguntas y se puede intentar.
- [ ] Ventana de incógnito: entrar con `docente1` (puede editar) y con `estudiante1` (no puede editar). 📸 Captura de cada uno.
- [ ] Si los colores no cambian: Administración del sitio → Desarrollo → Purgar todas las cachés.

## Fase 9 – Pruebas, OVA y documento (todos)

### 9.1 Pruebas
Llenar [`pruebas.md`](pruebas.md) con resultado y captura de cada una.

### 9.2 Comandos de administración (para la sustentación)

```bash
# Moodle
sudo systemctl status apache2 postgresql
sudo systemctl restart apache2
sudo tail -n 50 /var/log/apache2/moodle_error.log
sudo -u postgres psql -d moodle -c "SELECT id, username FROM mdl_user;"

# Portal
cd /opt/san-luquitas-infra
docker compose ps
docker compose stop
docker compose start
docker compose restart app
docker compose logs -f app
docker exec -it sl-portal-db sh -c 'mariadb -uroot -p"$MARIADB_ROOT_PASSWORD" "$MARIADB_DATABASE" -e "SELECT * FROM novedades;"'

# Servidor
sudo ufw status verbose
sudo ss -tlnp
df -h
free -h
sudo tail -n 30 /var/log/sanluquitas-backup.log
```

### 9.3 Exportar la OVA
1. Verificar que Moodle y el portal funcionan.
2. Apagar bien: `sudo poweroff`.
3. VirtualBox → File → **Export Appliance** → `sanluquitas-server` → formato OVF 2.0 → `sanluquitas-server.ova`.
4. Copiar también la carpeta del último backup (`/backup/FECHA`) fuera de la VM. Se saca con:

```bash
# en el Mac
scp -P 2222 -r adsml@localhost:/backup/FECHA ./backup-entrega/
```

(Si da error de permisos, primero en el servidor: `sudo cp -r /backup/FECHA /home/adsml/ && sudo chown -R adsml /home/adsml/FECHA` y copiar desde `/home/adsml/FECHA`.)

**Limitaciones a documentar:**
- La VM es ARM64 (Mac M4). El `.ova` solo se importa en VirtualBox sobre Apple Silicon u otro host ARM; en un PC Intel/AMD no arranca. Por eso la demostración se hace con la VM corriendo en el Mac.
- La red en puente por Wi-Fi no funcionó (el Mac no llegaba a la VM), por eso se usa NAT + reenvío de puertos. Desde otros equipos se entra con la IP del Mac; para eso hay que cambiar el `wwwroot` de Moodle a esa IP (ver nota de la prueba 8 en [`pruebas.md`](pruebas.md)).
- Sin HTTPS (laboratorio en red local).

### 9.4 Instrucciones de importación (para el documento)
1. VirtualBox → File → Import Appliance → `sanluquitas-server.ova`.
2. Red: Adapter 1 en **NAT** con el reenvío de puertos 2222→22, 8081→80 y 8080→8080 (viene dentro del `.ova`; revisar que siga).
3. Encender y entrar como `adsml` (consola o `ssh -p 2222 adsml@localhost`).
4. Abrir http://localhost:8081 (Moodle) y http://localhost:8080 (portal).
5. Si no carga el portal: `cd /opt/san-luquitas-infra && docker compose up -d`.

### 9.5 Documento PDF
Seguir los 20 apartados del enunciado. Material ya listo en el repo:
- Arquitectura y diagrama → [`arquitectura.md`](arquitectura.md)
- Pruebas → [`pruebas.md`](pruebas.md)
- Registro de IA → [`registro-ia.md`](registro-ia.md)
- Seguridad → [`acceso-remoto.md`](acceso-remoto.md) + UFW + redes Docker
- Capturas → `docs/capturas/`
