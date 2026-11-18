#!/usr/bin/env bash
# Backup completo: Moodle (BD + moodledata + config.php) y Portal (BD + archivos + .env).
# Uso: sudo ./scripts/backup.sh
set -euo pipefail

# ---- Configuración ----
BACKUP_ROOT=/backup
DIAS_A_GUARDAR=7
MOODLE_DIR=/var/www/moodle
MOODLE_DATA=/var/moodledata
MOODLE_DB=moodle
REPO_DIR=/opt/san-luquitas-infra
PORTAL_DB_CONTAINER=sl-portal-db
PORTAL_STORAGE_VOLUME=sanluquitas_portal_storage
# -----------------------

if [ "$(id -u)" -ne 0 ]; then
    echo "Ejecutar con sudo"; exit 1
fi

FECHA=$(date +%Y-%m-%d_%H%M)
DEST="$BACKUP_ROOT/$FECHA"
mkdir -p "$DEST"
chmod 700 "$DEST"

if [ -d "$MOODLE_DIR/admin/cli" ]; then
    MOODLE_CLI="$MOODLE_DIR/admin/cli"
else
    MOODLE_CLI="$MOODLE_DIR/public/admin/cli"
fi

log() { echo "[$(date '+%F %T')] $*"; }

log "Inicio del backup en $DEST"

# Moodle: modo mantenimiento para que nadie escriba mientras se copia
log "Moodle: activando modo mantenimiento"
sudo -u www-data php "$MOODLE_CLI/maintenance.php" --enable >/dev/null
# Si algo falla, igual se quita el modo mantenimiento
trap 'sudo -u www-data php "$MOODLE_CLI/maintenance.php" --disable >/dev/null' EXIT

log "Moodle: base de datos ($MOODLE_DB)"
sudo -u postgres pg_dump -Fc "$MOODLE_DB" > "$DEST/moodle_db.dump"

log "Moodle: moodledata"
tar -czf "$DEST/moodledata.tar.gz" -C "$(dirname "$MOODLE_DATA")" \
    --exclude="$(basename "$MOODLE_DATA")/cache" \
    --exclude="$(basename "$MOODLE_DATA")/localcache" \
    --exclude="$(basename "$MOODLE_DATA")/sessions" \
    --exclude="$(basename "$MOODLE_DATA")/temp" \
    --exclude="$(basename "$MOODLE_DATA")/trashdir" \
    "$(basename "$MOODLE_DATA")"

log "Moodle: config.php"
cp "$MOODLE_DIR/config.php" "$DEST/moodle_config.php"

sudo -u www-data php "$MOODLE_CLI/maintenance.php" --disable >/dev/null
trap - EXIT
log "Moodle: modo mantenimiento desactivado"

# Portal Laravel
log "Portal: base de datos"
docker exec "$PORTAL_DB_CONTAINER" sh -c \
    'mariadb-dump -uroot -p"$MARIADB_ROOT_PASSWORD" --single-transaction "$MARIADB_DATABASE"' \
    > "$DEST/portal_db.sql"

log "Portal: archivos (volumen $PORTAL_STORAGE_VOLUME)"
docker run --rm -v "$PORTAL_STORAGE_VOLUME":/data:ro -v "$DEST":/out alpine \
    tar -czf /out/portal_storage.tar.gz -C /data .

log "Portal: .env"
cp "$REPO_DIR/.env" "$DEST/portal.env"

chmod 600 "$DEST"/*

# Borrar backups viejos
find "$BACKUP_ROOT" -maxdepth 1 -type d -name '20*' -mtime +"$DIAS_A_GUARDAR" -exec rm -rf {} +

log "Backup terminado:"
ls -lh "$DEST"
