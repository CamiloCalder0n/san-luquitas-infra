# Contexto – Hackathon InfraTech "San Luquitas Digital"

> Archivo para pegar al inicio de cualquier chat nuevo con Claude (Cami o Santiago). Leer completo antes de responder.

## Qué es

Actividad final de **Infraestructura Tecnológica** (UNAB). Somos una "consultora" que monta un **servidor Linux** con dos aplicaciones para la **IE San Luquitas** (colegio rural ficticio):

1. **Moodle "San Luquitas Digital"** con el curso "Mis primeros pasos en programación" (4.° y 5.° de primaria): 3 unidades, mínimo 3 recursos, un quiz o tarea, un foro, admin, un docente y 3 estudiantes ficticios.
2. **Laravel "Portal Institucional San Luquitas"** en **Docker** (obligatorio): inicio, institución, recursos, novedades con registro y consulta en la base de datos, enlace a Moodle.

Lo que más pesa es la **infraestructura**, no el código: persistencia, seguridad (firewall, usuarios, permisos), **backup con restauración demostrada**, acceso desde otro equipo de la red, administración de servicios y logs, documento PDF de 20 apartados, matriz comparativa, registro de uso de IA (5 %) y sustentación en vivo.

## Equipo

| Persona | Parte |
|---|---|
| Juan Camilo Calderón (Cami) | VM, servidor, seguridad, Moodle, despliegue, backups. Dueño del repo |
| Santiago Steven Ruiz | Portal Laravel (código) y su prueba en Docker |
| Pablo Samuel y Juan Mora | Contenido del curso en Moodle, diagrama, matriz, documento PDF |

## Arquitectura (decidida)

| Componente | Elección | Por qué |
|---|---|---|
| SO | Ubuntu Server 24.04 LTS (ARM, VM en el Mac M4 de Cami) | Soporte hasta 2029 y trae PHP 8.3. Ubuntu 26.04 trae PHP 8.5, que Moodle 5.3 no soporta |
| Moodle | 5.3 LTS en Apache + PHP 8.3, directo en Linux, puerto 80 | Forma estándar; LTS para un colegio con poco soporte técnico |
| BD Moodle | PostgreSQL 17 (repo oficial), solo localhost | Moodle 5.3 pide PG 17+; pasos del manual del profe |
| Portal | Laravel 12 en Docker Compose: nginx + PHP-FPM + MariaDB 11.4, puerto 8080 | Obligatorio Docker; la BD queda en red interna sin puerto expuesto |
| Backups | Script diario a un disco aparte `/backup` + scripts de restauración | Exigido por la rúbrica |
| Acceso | `sanluquitas.local` (mDNS) en la red local; Tailscale para Santiago | Funciona sin internet en el colegio y sin abrir puertos |

Modalidad: **Tradicional + Docker**.

## Repo

`github.com/CamiloCalder0n/san-luquitas-infra` (privado, Santiago colaborador).

```
portal-laravel/   código Laravel (Santiago)
docker/           Dockerfile (targets app y web), php.ini, entrypoint, nginx
compose.yaml      servicios app, web, db
.env.example      variables (el .env real no se sube)
moodle/           vhost Apache y php.ini de Moodle
scripts/          deploy, backup, restore-moodle, restore-portal, cron
docs/             guía paso a paso, arquitectura, pruebas, registro IA, capturas, guías por persona
```

Flujo: rama por tarea → PR → el otro revisa → merge. Nadie sube directo a `main`.

## Estado

- [x] Repo base creado y subido
- [ ] VM + Ubuntu (Cami)
- [ ] Servidor base, Tailscale, usuario de Santiago (Cami)
- [ ] Moodle (Cami)
- [ ] Portal Laravel (Santiago) → `docs/guias/GUIA_01_SANTIAGO_PORTAL.md`
- [ ] Despliegue del portal en el servidor (Cami)
- [ ] Backups + prueba de restauración (Cami)
- [ ] Contenido EVA (Pablo y Juan)
- [ ] Pruebas, OVA, documento, sustentación (todos)

## Cómo queremos que trabaje Claude

- Español colombiano casual, respuestas directas y listas para copiar y pegar.
- En documentos de la U: estilo sencillo de estudiante, puntual, poco texto. Que no se note tanta IA.
- Claude Code hace commit, push y abre el PR después de la revisión doble, y luego corre la skill `revision-pr` hasta luz verde (ver "Flujo de entrega" en `CLAUDE.md`). El merge lo hace Cami; nunca merge, tag ni force-push. Sin coautoría de Claude en commits ni PR.
- Nunca secretos en el repo ni en el chat.
- No inventar resultados; si falta evidencia, avisar.
- Si algo no cuadra con el enunciado, preguntar antes de improvisar.
- Anotar en `docs/registro-ia.md` los prompts importantes (cuenta para la nota).
