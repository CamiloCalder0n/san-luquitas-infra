# Arquitectura

## Diagrama

```mermaid
flowchart LR
    subgraph Red local del colegio
        U1[PC docente]
        U2[PC / tablet estudiantes]
    end

    subgraph VM["Servidor Ubuntu 24.04 (sanluquitas.local)"]
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

    U1 & U2 --> UFW
    UFW -->|:80| AP
    UFW -->|:8080| WEB
    AP --> PG
    AP --> MD
    WEB --> APP --> DB
    PG -. pg_dump .-> BK
    MD -. tar .-> BK
    DB -. mariadb-dump .-> BK
```

## Servicios y puertos

| Servicio | Dónde | Puerto | Expuesto a la red |
|---|---|---|---|
| SSH | Linux | 22 | Sí (solo con llave) |
| Apache (Moodle) | Linux | 80 | Sí |
| nginx (Portal) | Docker | 8080 → 80 | Sí |
| PostgreSQL | Linux | 5432 | No (solo localhost) |
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
| Código Moodle | `/var/www/moodle` | Directorio (git) |
| Archivos Moodle | `/var/moodledata` | Directorio persistente |
| BD Moodle | `/var/lib/postgresql/17` | Directorio persistente |
| BD Portal | volumen `sanluquitas_portal_db` | Volumen Docker |
| Archivos Portal | volumen `sanluquitas_portal_storage` | Volumen Docker |
| Backups | `/backup` (disco 2) | Disco separado |

## Bases de datos

| App | Motor | BD | Usuario |
|---|---|---|---|
| Moodle | PostgreSQL 17 | `moodle` | `moodleuser` |
| Portal | MariaDB 11.4 | `portal_sanluquitas` | `portal_user` |

Cada aplicación tiene su propia base de datos y su propio usuario, sin permisos sobre la otra.
