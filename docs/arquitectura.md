# Arquitectura

## Diagrama

```mermaid
flowchart LR
    subgraph Red local del colegio
        U1[PC docente]
        U2[PC / tablet estudiantes]
    end

    MAC{{Mac anfitrión<br/>VirtualBox NAT<br/>2222→22 · 8081→80 · 8080→8080}}

    subgraph VM["VM Ubuntu Server 24.04.5 ARM64 (sanluquitas)"]
        UFW{{Firewall UFW<br/>22 · 80 · 8080}}

        subgraph Linux
            AP[Apache 2.4 + PHP 8.3<br/>Moodle 5.3 :80]
            PG[(PostgreSQL 17<br/>BD moodle)]
            MD[/moodledata/]
        end

        subgraph Docker["Docker Compose (sanluquitas)"]
            WEB[nginx :8080]
            APP[Laravel PHP-FPM]
            DB[(MariaDB 11.4<br/>BD portal)]
        end

        BK[/Disco /backup/]
    end

    U1 & U2 --> MAC --> UFW
    UFW -->|:80| AP
    UFW -->|:8080| WEB
    AP --> PG
    AP --> MD
    WEB --> APP --> DB
    PG -. pg_dump .-> BK
    MD -. tar .-> BK
    DB -. mariadb-dump .-> BK
```

## Red de la VM

VirtualBox con **NAT + reenvío de puertos** (la red en puente por Wi-Fi no dejaba que el Mac llegara a la VM). Desde otros equipos se entra con la IP del Mac.

| Puerto en el Mac | Puerto en la VM | Servicio |
|---|---|---|
| 2222 | 22 | SSH (`ssh -p 2222 adsml@localhost`) |
| 8081 | 80 | Moodle (http://localhost:8081) |
| 8080 | 8080 | Portal (http://localhost:8080) |

## Servicios y puertos

| Servicio | Dónde | Puerto | Expuesto a la red |
|---|---|---|---|
| SSH | Linux | 22 | Sí (solo con llave, usuario `adsml`) |
| Apache (Moodle) | Linux | 80 | Sí |
| nginx (Portal) | Docker | 8080 → 80 | Sí |
| PostgreSQL | Linux | 5432 | No (solo 127.0.0.1) |
| PHP-FPM (Laravel) | Docker | 9000 | No (red interna) |
| MariaDB | Docker | 3306 | No (red interna) |

## Redes Docker

| Red | Tipo | Quién está |
|---|---|---|
| `frontend` | bridge | web |
| `backend` | bridge interna (sin salida) | web, app, db |

La base de datos y la app no tienen puertos publicados. Solo nginx recibe tráfico desde fuera.

## Almacenamiento

| Dato | Ubicación | Tipo |
|---|---|---|
| Código Moodle | `/var/www/moodle` (web: `public/`) | Directorio (git, rama MOODLE_503_STABLE) |
| Archivos Moodle | `/var/moodledata` | Directorio persistente |
| BD Moodle | `/var/lib/postgresql/17` | Directorio persistente |
| BD Portal | volumen `sanluquitas_portal_db` | Volumen Docker |
| Archivos Portal | volumen `sanluquitas_portal_storage` | Volumen Docker |
| Backups | `/backup` (disco 2: `/dev/sdb`, ext4, etiqueta `backup`) | Disco separado de 10 GB |

## Bases de datos

| App | Motor | BD | Usuario |
|---|---|---|---|
| Moodle | PostgreSQL 17 | `moodle` | `moodleuser` |
| Portal | MariaDB 11.4 | `portal_sanluquitas` | `portal_user` |

Cada aplicación tiene su propia base de datos y su propio usuario, sin permisos sobre la otra.
