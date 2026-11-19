# San Luquitas Digital – Infraestructura

Hackathon InfraTech · Infraestructura Tecnológica · UNAB

Servidor Linux que aloja dos aplicaciones para la IE San Luquitas (caso académico ficticio):

- **San Luquitas Digital**: Moodle con el curso "Mis primeros pasos en programación".
- **Portal Institucional San Luquitas**: aplicación Laravel desplegada con Docker.

## Equipo

| Integrante | Rol |
|---|---|
| Juan Camilo Calderón | Servidor (VM), Moodle, seguridad y backups |
| Santiago Steven Ruiz | Portal Laravel y Docker |
| Pablo Samuel | Contenido del EVA y documentación |
| Juan Mora | Contenido del EVA y documentación |

## Arquitectura

| Componente | Tecnología | Dónde corre |
|---|---|---|
| Sistema operativo | Ubuntu Server 24.04.5 LTS (ARM64) | VM VirtualBox en Mac M4 (2 vCPU, 4 GB RAM, disco 30 GB + disco 10 GB para backups) |
| Moodle 5.3 LTS | Apache 2.4 + PHP 8.3.6 | Directo en Linux, puerto 80 (en el Mac: 8081) |
| BD Moodle | PostgreSQL 17 (repo PGDG) | Directo en Linux (solo 127.0.0.1) |
| Portal Laravel | PHP-FPM 8.3 + Nginx | Docker, puerto 8080 |
| BD Laravel | MariaDB 11.4 | Docker, red interna (sin puerto publicado) |
| Backups | Scripts bash + cron | Disco aparte montado en `/backup` |

Modalidad: **Tradicional + Docker**. Diagrama y detalles en [`docs/arquitectura.md`](docs/arquitectura.md).

**Guía completa de instalación: [`docs/guia-paso-a-paso.md`](docs/guia-paso-a-paso.md)**

## Acceso

La VM usa red **NAT con reenvío de puertos** de VirtualBox (la red en puente por Wi-Fi no dejaba que el Mac llegara a la VM).

| Servicio | Desde el Mac | Desde otro equipo de la red | Puerto en la VM |
|---|---|---|---|
| SSH | `ssh -p 2222 adsml@localhost` | `ssh -p 2222 adsml@IP_DEL_MAC` | 22 |
| Moodle | http://localhost:8081 | http://IP_DEL_MAC:8081 | 80 |
| Portal | http://localhost:8080 | http://IP_DEL_MAC:8080 | 8080 |

Usuario administrador del servidor: `adsml` (entra solo con llave SSH).

Para abrir desde otro equipo de la red: `sudo /opt/san-luquitas-infra/scripts/cambiar_url.sh IP_DEL_MAC` (cambia Moodle, la portada y el portal a esa IP). Para volver: `... cambiar_url.sh localhost`.

## Estructura del repo

```
san-luquitas-infra/
├── portal-laravel/        # Código de la app Laravel (Santiago)
├── docker/
│   ├── Dockerfile         # Imagen de la app (target app) y de nginx (target web)
│   ├── php/               # php.ini y entrypoint del contenedor
│   └── nginx/             # Configuración de nginx del portal
├── compose.yaml           # Servicios: app, web, db
├── .env.example           # Variables de ejemplo (el .env real NO se sube)
├── moodle/                # Apache, PHP, script de configuración, logos, usuarios.csv
├── scripts/               # deploy, backup y restauración
└── docs/                  # Arquitectura, pruebas, registro de IA y capturas
```

## Despliegue del portal en el servidor

El repo es público, así que el servidor lo clona por https (sin llaves):

```bash
sudo git clone https://github.com/CamiloCalder0n/san-luquitas-infra.git /opt/san-luquitas-infra
sudo chown -R adsml:adsml /opt/san-luquitas-infra
cd /opt/san-luquitas-infra
cp .env.example .env        # solo la primera vez, luego editar claves
nano .env
./scripts/deploy.sh
docker compose exec app php artisan db:seed --force   # solo la primera vez
```

## Configuración de Moodle (identidad, curso y usuarios)

```bash
sudo -u www-data php /opt/san-luquitas-infra/moodle/configurar_moodle.php
```

## Comandos de administración

```bash
docker compose ps                 # estado de los contenedores
docker compose logs -f app        # logs de Laravel
docker compose restart            # reiniciar el portal
docker compose down               # detener (los volúmenes se conservan)
docker compose up -d              # iniciar

sudo systemctl status apache2 postgresql   # estado de Moodle
sudo tail -f /var/log/apache2/moodle_error.log

sudo /opt/san-luquitas-infra/scripts/backup.sh               # backup manual
sudo /opt/san-luquitas-infra/scripts/restore-moodle.sh FECHA # restaurar Moodle
sudo /opt/san-luquitas-infra/scripts/restore-portal.sh FECHA # restaurar portal
```

## Tareas

### Servidor (Cami)
- [x] VM creada (disco 30 GB + disco 10 GB, red NAT con reenvío de puertos)
- [x] Ubuntu Server instalado y acceso por SSH con llave (puerto 2222)
- [x] Disco `/backup`, UFW
- [x] Docker instalado
- [x] Apache + PHP 8.3 + PostgreSQL 17
- [x] Moodle 5.3 instalado y con cron
- [ ] Identidad, curso y usuarios (`moodle/configurar_moodle.php`)
- [ ] Portal desplegado en el servidor
- [ ] Backups automáticos + prueba de restauración

### Portal (Santiago)
- [ ] Proyecto Laravel creado en `portal-laravel/`
- [ ] Páginas: inicio, institución, recursos, enlace a Moodle
- [ ] Novedades: crear, listar, editar, eliminar (BD)
- [ ] Probado con `docker compose up -d --build`

### EVA y documento (Pablo y Juan)
- [ ] Contenido de las 3 unidades, recursos, quiz y foro
- [ ] Usuarios: admin, docente, 3 estudiantes
- [ ] Diagrama, matriz comparativa, registro de IA
- [ ] Documento PDF final

Cómo trabajar en el repo: ver [`CONTRIBUTING.md`](CONTRIBUTING.md).

## Limitaciones conocidas

- La VM es **ARM64** (Mac M4). El `.ova` solo se importa en equipos Apple Silicon u otro host ARM; en un PC Intel/AMD no arranca. Por eso la demo se hace con la VM corriendo en el Mac.
- La red en puente por Wi-Fi no funcionó (el Mac no llegaba a la VM). Se usa NAT + reenvío de puertos; desde otros equipos se entra con la IP del Mac.
- Sin HTTPS: es un laboratorio en red local.
