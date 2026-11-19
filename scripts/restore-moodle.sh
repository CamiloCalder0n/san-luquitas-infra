#!/usr/bin/env bash
# Restaura Moodle (BD + moodledata) desde un backup.
# Uso: sudo ./scripts/restore-moodle.sh 2026-11-20_0200
set -euo pipefail

BACKUP_ROOT=/backup
MOODLE_DIR=/var/www/moodle
MOODLE_DATA=/var/moodledata
MOODLE_DB=moodle
MOODLE_DB_USER=moodleuser

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
for f in moodle_db.dump moodledata.tar.gz; do
    [ -f "$SRC/$f" ] || { echo "No existe $SRC/$f"; exit 1; }
done

if [ -d "$MOODLE_DIR/admin/cli" ]; then
    MOODLE_CLI="$MOODLE_DIR/admin/cli"
else
    MOODLE_CLI="$MOODLE_DIR/public/admin/cli"
fi

read -r -p "Se reemplazan la BD y moodledata actuales por el backup $FECHA. Escribe SI para continuar: " R
[ "$R" = "SI" ] || { echo "Cancelado"; exit 1; }

echo "1/5 Deteniendo Apache"
systemctl stop apache2

echo "2/5 Restaurando base de datos"
sudo -u postgres dropdb --if-exists --force "$MOODLE_DB"
sudo -u postgres createdb -O "$MOODLE_DB_USER" -E UTF8 -T template0 "$MOODLE_DB"
sudo -u postgres pg_restore -d "$MOODLE_DB" --no-owner --role="$MOODLE_DB_USER" < "$SRC/moodle_db.dump"

echo "3/5 Restaurando moodledata (la actual se guarda como respaldo)"
ANTES="${MOODLE_DATA}.antes-$(date +%Y%m%d%H%M)"
mv "$MOODLE_DATA" "$ANTES"
tar -xzf "$SRC/moodledata.tar.gz" -C "$(dirname "$MOODLE_DATA")"
chown -R www-data:www-data "$MOODLE_DATA"
find "$MOODLE_DATA" -type d -exec chmod 2770 {} +

echo "4/5 Iniciando Apache y limpiando cachés"
systemctl start apache2
sudo -u www-data php "$MOODLE_CLI/purge_caches.php"

echo "5/5 Quitando modo mantenimiento"
sudo -u www-data php "$MOODLE_CLI/maintenance.php" --disable

echo "Restauración terminada. La moodledata anterior quedó en: $ANTES"
echo "Cuando verifiques que todo está bien puedes borrarla con: sudo rm -rf $ANTES"
