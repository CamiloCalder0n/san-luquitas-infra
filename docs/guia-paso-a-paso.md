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
| 8 | Contenido del EVA | Pablo y Juan |
| 9 | Pruebas, exportar OVA y documento | Todos |

Las fases 4 (Cami) y 5 (Santiago) se hacen **al mismo tiempo**.

Reemplazar siempre lo que está en MAYÚSCULAS (`TU_USUARIO`, `CLAVE_...`, etc.).

---

## Fase 0 – Repo en GitHub (Cami)

1. GitHub → **New repository** → nombre `san-luquitas-infra` → **Private** → sin README (ya viene en el zip).
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
4. (Opcional) Settings → **Branches** → regla para `main`: requerir pull request. En repos privados con cuenta gratis puede no estar disponible; en ese caso solo se respeta el acuerdo de `CONTRIBUTING.md`.

---

## Fase 1 – Crear la VM (Cami, en el Mac)

### 1.1 Descargas
- VirtualBox: https://www.virtualbox.org/wiki/Downloads → **macOS / Apple Silicon hosts**
- Ubuntu Server 24.04 LTS ARM: https://ubuntu.com/download/server/arm → `ubuntu-24.04.x-live-server-arm64.iso`

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
- **Network** → Adapter 1 → **Bridged Adapter** → `en0: Wi-Fi`.

### 1.3 Instalar Ubuntu

| Pantalla | Opción |
|---|---|
| Language | English |
| Keyboard | Spanish (Latin American) |
| Type of install | Ubuntu Server |
| Network | DHCP (anotar la IP) |
| Storage | Use an entire disk → **el de 30 GB** |
| Profile | Name: `Administrador San Luquitas` · Server: `sanluquitas` · User: `admsl` |
| Ubuntu Pro | Skip |
| SSH | ✅ Install OpenSSH server |
| Featured snaps | Ninguno |

Al terminar → **Reboot Now** → Enter si pide retirar el ISO. Entrar con `admsl` y ver la IP:

```bash
ip -br a
```

📸 Capturas: configuración de la VM (RAM, CPU, 2 discos, red) y pantallas de instalación.

---

## Fase 2 – Servidor base (Cami, por SSH desde el Mac)

### 2.1 Llave SSH desde el Mac

```bash
ssh-keygen -t ed25519 -C "cami-mac"
ssh-copy-id admsl@IP_DE_LA_VM
ssh admsl@IP_DE_LA_VM
```

### 2.2 Actualizar y paquetes base

```bash
sudo apt update && sudo apt -y upgrade
sudo timedatectl set-timezone America/Bogota
sudo apt -y install avahi-daemon curl git unzip htop ufw ca-certificates nano
```

Desde el Mac comprobar el nombre:

```bash
ping -c 3 sanluquitas.local
```

Desde aquí se puede entrar con `ssh admsl@sanluquitas.local`.

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
df -h /backup
```

### 2.4 Firewall

```bash
sudo ufw default deny incoming
sudo ufw default allow outgoing
sudo ufw allow OpenSSH
sudo ufw allow 80/tcp
sudo ufw allow 8080/tcp
sudo ufw allow 5353/udp
sudo ufw enable
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
sudo usermod -aG docker admsl
exit
```

Volver a entrar y probar:

```bash
ssh admsl@sanluquitas.local
docker --version
docker compose version
docker run --rm hello-world
```

📸 Capturas: `ip -br a`, `lsblk`, `df -h /backup`, `ufw status verbose`, `docker run hello-world`.

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
sudo usermod -aG devs admsl
sudo usermod -aG devs santiago
sudo mkdir -p /opt/san-luquitas-infra
sudo chown admsl:devs /opt/san-luquitas-infra
sudo chmod 2775 /opt/san-luquitas-infra
```

Blindar SSH (dejar una sesión abierta mientras se prueba):

```bash
sudo tee /etc/ssh/sshd_config.d/01-sanluquitas.conf > /dev/null <<'EOF'
PermitRootLogin no
PasswordAuthentication no
KbdInteractiveAuthentication no
PubkeyAuthentication yes
AllowUsers admsl santiago
MaxAuthTries 3
LoginGraceTime 30
EOF
sudo sshd -t && sudo systemctl restart ssh
sudo apt -y install fail2ban
sudo systemctl enable --now fail2ban
```

**Santiago:** `ssh santiago@100.X.Y.Z`

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

Crear la base y el usuario de Moodle:

```bash
sudo -u postgres psql -c "CREATE USER moodleuser WITH PASSWORD 'CLAVE_BD_MOODLE';"
sudo -u postgres psql -c "CREATE DATABASE moodle WITH OWNER moodleuser ENCODING 'UTF8' TEMPLATE template0;"
sudo -u postgres psql -c "\l moodle"
```

Comprobar que solo escucha en localhost:

```bash
sudo ss -tlnp | grep 5432
```

### 4.2 Apache + PHP 8.3

```bash
sudo apt -y install apache2 libapache2-mod-php8.3 php8.3 php8.3-cli php8.3-pgsql \
  php8.3-intl php8.3-xml php8.3-mbstring php8.3-curl php8.3-zip php8.3-gd \
  php8.3-soap php8.3-bcmath php8.3-readline
php -v
php -m | grep -E "sodium|intl|pgsql|zip|gd|soap"
```

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
    ServerName sanluquitas.local
    ServerAlias *
    DocumentRoot /var/www/moodle/public

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

### 4.5 Instalar Moodle por consola

El instalador necesita escribir `config.php`, así que por un momento la carpeta es de `www-data`:

```bash
sudo chown -R www-data:www-data /var/www/moodle

sudo -u www-data php /var/www/moodle/admin/cli/install.php \
  --lang=es \
  --wwwroot=http://sanluquitas.local \
  --dataroot=/var/moodledata \
  --dbtype=pgsql \
  --dbhost=localhost \
  --dbname=moodle \
  --dbuser=moodleuser \
  --dbpass='CLAVE_BD_MOODLE' \
  --prefix=mdl_ \
  --fullname="San Luquitas Digital" \
  --shortname="SanLuquitas" \
  --summary="Entorno Virtual de Aprendizaje de la IE San Luquitas" \
  --adminuser=admin \
  --adminpass='CLAVE_ADMIN_MOODLE' \
  --adminemail=admin@sanluquitas.edu.co \
  --agree-license \
  --non-interactive
```

Tarda varios minutos. Al terminar, devolver la carpeta a root y proteger `config.php`:

```bash
sudo chown -R root:root /var/www/moodle
sudo chown root:www-data /var/www/moodle/config.php
sudo chmod 640 /var/www/moodle/config.php
```

### 4.6 Cron de Moodle

```bash
echo '* * * * * www-data /usr/bin/php /var/www/moodle/admin/cli/cron.php >/dev/null 2>&1' | sudo tee /etc/cron.d/moodle
sudo -u www-data php /var/www/moodle/admin/cli/cron.php | tail -n 5
```

(En la fase 7 este archivo se reemplaza por el del repo, que incluye también el backup.)

### 4.7 Verificar

- Abrir http://sanluquitas.local y entrar como `admin`.
- Administración del sitio → Servidor → **Entorno**: todo en verde. Si sale aviso del router, revisar la línea `FallbackResource` del VirtualHost.
- Administración del sitio → Notificaciones: sin errores.

📸 Capturas: `psql --version`, `php -v`, `apache2ctl configtest`, pantalla de Entorno, página de inicio.

---

## Fase 5 – Portal Laravel (Santiago)

Ver también [`../CONTRIBUTING.md`](../CONTRIBUTING.md).

### 5.1 Crear el proyecto

```bash
git clone https://github.com/USUARIO_DE_CAMI/san-luquitas-infra.git
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
| Moodle | botón en el menú | `env('MOODLE_URL')` |

Modelo sugerido:

```bash
cd portal-laravel
php artisan make:model Novedad -mcr
```

Migración `novedades`: `id`, `titulo` (string), `contenido` (text), `categoria` (string: noticia/comunicado), `fecha_publicacion` (date), `timestamps`.

En el modelo: `protected $table = 'novedades';` y `protected $fillable = ['titulo','contenido','categoria','fecha_publicacion'];`

Para el enlace a Moodle, en `config/app.php` agregar `'moodle_url' => env('MOODLE_URL', 'http://sanluquitas.local'),` y en las vistas usar `config('app.moodle_url')` (con `config:cache`, `env()` no funciona fuera de los archivos de config).

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

### 6.1 Deploy key (el servidor solo puede leer el repo)

```bash
ssh-keygen -t ed25519 -f ~/.ssh/github_deploy -N "" -C "sanluquitas-deploy"
cat ~/.ssh/github_deploy.pub
```

GitHub → repo → Settings → **Deploy keys** → Add → pegar → **sin** "Allow write access".

```bash
cat >> ~/.ssh/config <<'EOF'
Host github.com
    IdentityFile ~/.ssh/github_deploy
    IdentitiesOnly yes
EOF
chmod 600 ~/.ssh/config

git clone git@github.com:TU_USUARIO/san-luquitas-infra.git /opt/san-luquitas-infra
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

En `.env` cambiar: `APP_KEY`, `DB_PASSWORD`, `DB_ROOT_PASSWORD`. Guardar con Ctrl+O, Enter, Ctrl+X.

```bash
chmod +x scripts/*.sh
./scripts/deploy.sh
```

La primera vez tarda (descarga imágenes y compila). Verificar:

```bash
docker compose ps
docker compose logs --tail=50 app
docker images
docker network ls
docker volume ls
```

Abrir http://sanluquitas.local:8080.

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

## Fase 8 – Contenido del EVA (Pablo y Juan, con usuario admin)

1. **Identidad:** Administración del sitio → Apariencia → Temas → Boost → logo y colores. Página principal → nombre "San Luquitas Digital" y descripción.
2. **Curso:** Administración → Cursos → Agregar curso:
   - Nombre: `Mis primeros pasos en programación`
   - Nombre corto: `PROG-4-5`
   - Descripción: población (4.° y 5.° de primaria), objetivos.
   - Formato: Temas, 3 secciones.
3. **Unidades:**
   - Unidad 1: Introducción al pensamiento computacional: secuencias y patrones
   - Unidad 2: Algoritmos cotidianos: instrucciones y resolución de problemas
   - Unidad 3: Programación visual: primeros pasos con Scratch
4. **Recursos (mínimo 3):** una Página por unidad, un Archivo PDF, una URL a https://scratch.mit.edu, un video.
5. **Actividad evaluativa:** un Cuestionario de 5 preguntas en la Unidad 1 y una Tarea en la Unidad 3.
6. **Foro:** "Cuéntanos qué aprendiste".
7. **Usuarios:** Administración → Usuarios → Agregar usuario:
   - `docente1` (rol Profesor en el curso)
   - `estudiante1`, `estudiante2`, `estudiante3` (rol Estudiante)
   - Solo nombres ficticios.
8. **Matricular:** Curso → Participantes → Matricular usuarios.
9. **Roles:** entrar con cada usuario (ventana privada) y tomar captura de lo que ve cada uno.

---

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
scp -r admsl@sanluquitas.local:/backup/FECHA ./backup-entrega/
```

(Si da error de permisos, primero en el servidor: `sudo cp -r /backup/FECHA /home/admsl/ && sudo chown -R admsl /home/admsl/FECHA`.)

**Limitación a documentar:** la VM es ARM64 (Mac M4). Se importa en VirtualBox sobre Apple Silicon u otro host ARM; en un PC Intel/AMD no arranca. Por eso la demostración se hace con la VM corriendo en el Mac.

### 9.4 Instrucciones de importación (para el documento)
1. VirtualBox → File → Import Appliance → `sanluquitas-server.ova`.
2. Red: Bridged a la tarjeta de red del equipo.
3. Encender, entrar como `admsl`.
4. Abrir http://sanluquitas.local y http://sanluquitas.local:8080.
5. Si no carga el portal: `cd /opt/san-luquitas-infra && docker compose up -d`.

### 9.5 Documento PDF
Seguir los 20 apartados del enunciado. Material ya listo en el repo:
- Arquitectura y diagrama → [`arquitectura.md`](arquitectura.md)
- Pruebas → [`pruebas.md`](pruebas.md)
- Registro de IA → [`registro-ia.md`](registro-ia.md)
- Seguridad → [`acceso-remoto.md`](acceso-remoto.md) + UFW + redes Docker
- Capturas → `docs/capturas/`
