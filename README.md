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
| Sistema operativo | Ubuntu Server 24.04 LTS | VM VirtualBox (2 vCPU, 4 GB RAM) |
| Moodle 5.3 LTS | Apache 2.4 + PHP 8.3 | Directo en Linux, puerto 80 |
| BD Moodle | PostgreSQL 17 | Directo en Linux (solo localhost) |
| Portal Laravel | PHP-FPM 8.3 + Nginx | Docker, puerto 8080 |
| BD Laravel | MariaDB 11.4 | Docker, red interna (sin puerto publicado) |
| Backups | Scripts bash + cron | Disco aparte montado en `/backup` |

Modalidad: **Tradicional + Docker**. Diagrama y detalles en [`docs/arquitectura.md`](docs/arquitectura.md).

**Guía completa de instalación: [`docs/guia-paso-a-paso.md`](docs/guia-paso-a-paso.md)**

## Acceso

| Servicio | URL |
|---|---|
| Moodle | http://sanluquitas.local |
| Portal | http://sanluquitas.local:8080 |

`sanluquitas.local` funciona por mDNS (avahi). Si un equipo no lo resuelve, se usa la IP del servidor.

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
├── moodle/                # Configuración de Apache y PHP para Moodle
├── scripts/               # deploy, backup y restauración
└── docs/                  # Arquitectura, pruebas, registro de IA y capturas
```

## Despliegue del portal en el servidor

```bash
cd /opt/san-luquitas-infra
cp .env.example .env        # solo la primera vez, luego editar claves
nano .env
./scripts/deploy.sh
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
- [ ] VM creada (2 discos, red en puente)
- [ ] Ubuntu Server instalado y acceso por SSH
- [ ] Disco `/backup`, UFW, avahi
- [ ] Docker instalado
- [ ] Apache + PHP 8.3 + PostgreSQL
- [ ] Moodle instalado y con cron
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
