#!/usr/bin/env bash
# Restaura el portal Laravel (BD + archivos) desde un backup.
# Uso: sudo ./scripts/restore-portal.sh 2026-11-20_0200
set -euo pipefail

BACKUP_ROOT=/backup
REPO_DIR=/opt/san-luquitas-infra
PORTAL_DB_CONTAINER=sl-portal-db
PORTAL_STORAGE_VOLUME=sanluquitas_portal_storage

if [ "$(id -u)" -ne 0 ]; then
    echo "Ejecutar con sudo"; exit 1
fi

FECHA="${1:-}"
if [ -z "$FECHA" ]; then
    echo "Uso: sudo $0 FECHA"
    echo "Backups disponibles:"
    ls -1 "$BACKUP_ROOT" | grep '^20' || true
    exit 1
fi

SRC="$BACKUP_ROOT/$FECHA"
[ -f "$SRC/portal_db.sql" ] || { echo "No existe $SRC/portal_db.sql"; exit 1; }

read -r -p "Se reemplazan los datos actuales del portal por el backup $FECHA. Escribe SI para continuar: " R
[ "$R" = "SI" ] || { echo "Cancelado"; exit 1; }

echo "1/3 Restaurando base de datos"
docker exec -i "$PORTAL_DB_CONTAINER" sh -c \
    'mariadb -uroot -p"$MARIADB_ROOT_PASSWORD" "$MARIADB_DATABASE"' < "$SRC/portal_db.sql"

echo "2/3 Restaurando archivos"
if [ -f "$SRC/portal_storage.tar.gz" ]; then
    docker run --rm -v "$PORTAL_STORAGE_VOLUME":/data -v "$SRC":/in:ro alpine \
        sh -c 'rm -rf /data/* && tar -xzf /in/portal_storage.tar.gz -C /data'
fi

echo "3/3 Reiniciando la app"
cd "$REPO_DIR"
docker compose restart app

echo "Restauración del portal terminada."
