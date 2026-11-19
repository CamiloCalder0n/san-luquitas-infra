# Pruebas de funcionamiento

Para cada prueba: anotar el resultado y guardar la captura en `docs/capturas/` con el número de la prueba.

| # | Prueba | Procedimiento | Resultado esperado | Resultado | Captura |
|---|---|---|---|---|---|
| 1 | Acceso al servidor | `ssh -p 2222 adsml@localhost` (desde el Mac) | Entra por SSH con llave | | |
| 2 | Acceso a Moodle | Abrir http://localhost:8081 | Carga San Luquitas Digital | | |
| 3 | Acceso a Laravel | Abrir http://localhost:8080 | Carga el portal | | |
| 4 | Funcionamiento simultáneo | Abrir ambas en dos pestañas + `docker compose ps` y `systemctl status apache2` | Las dos responden | | |
| 5 | BD Moodle | Crear un usuario y consultarlo | Se guarda sin errores | | |
| 6 | BD Laravel | Crear una novedad y verla en el listado | Aparece en la lista | | |
| 7 | Acceso por roles | Entrar como admin, docente y estudiante | Cada uno ve opciones distintas | | |
| 8 | Acceso en red local | Desde un celular o PC en la misma red: http://IP_DEL_MAC:8081 y http://IP_DEL_MAC:8080 (ver nota) | Abren | | |
| 9 | Reinicio de servicios | `sudo reboot` y volver a abrir ambas apps | Todo arranca solo | | |
| 10 | Persistencia Docker | `docker compose down` y `docker compose up -d --build` | Las novedades siguen | | |
| 11 | Backup | `sudo ./scripts/backup.sh` | Se crea carpeta en /backup | | |
| 12 | Restauración Moodle | Borrar un recurso del curso → `restore-moodle.sh` | El recurso vuelve | | |
| 13 | Restauración Portal | Borrar una novedad → `restore-portal.sh` | La novedad vuelve | | |
| 14 | Puertos | `sudo ss -tlnp` y `sudo ufw status` | Solo 22, 80 y 8080 abiertos | | |
| 15 | SSH seguro | `ssh -p 2222 -o PubkeyAuthentication=no adsml@localhost` | Permission denied | | |
| 16 | BD no expuesta | En la VM: `sudo ss -tlnp \| grep -E '5432\|3306'` y `docker compose ps db` | PostgreSQL solo en 127.0.0.1; MariaDB sin puerto publicado | | |

**Nota prueba 8:** Moodle solo acepta la dirección de `wwwroot`. Para abrir desde otro equipo, primero cambiar todo a la IP del Mac (en el Mac: `ipconfig getifaddr en0`):

```bash
sudo /opt/san-luquitas-infra/scripts/cambiar_url.sh IP_DEL_MAC
```

Para volver a dejarlo solo en el Mac: `sudo /opt/san-luquitas-infra/scripts/cambiar_url.sh localhost`.
