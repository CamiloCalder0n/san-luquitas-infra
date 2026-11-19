#!/usr/bin/env bash
# Cambia la dirección con la que se abren Moodle (:8081) y el portal (:8080).
# Sirve para la demo: con localhost solo abre en el Mac; con la IP del Mac abre desde otros equipos de la red.
# Uso: sudo ./scripts/cambiar_url.sh 192.168.1.20
#      sudo ./scripts/cambiar_url.sh localhost
set -euo pipefail

NUEVO="${1:-}"
MOODLE_DIR=/var/www/moodle
REPO_DIR=/opt/san-luquitas-infra

if [ "$(id -u)" -ne 0 ] || [ -z "$NUEVO" ]; then
    echo "Uso: sudo $0 IP_DEL_MAC   (o localhost)"; exit 1
fi

ACTUAL=$(grep -oP "wwwroot\s*=\s*'http://\K[^:/']+" "$MOODLE_DIR/config.php")
echo "Dirección actual: $ACTUAL  ->  nueva: $NUEVO"

if [ "$ACTUAL" != "$NUEVO" ]; then
    # Moodle
    sed -i "s|http://$ACTUAL:8081|http://$NUEVO:8081|" "$MOODLE_DIR/config.php"
    # Enlaces guardados en la base de datos de Moodle (botón "Portal institucional" de la portada)
    sudo -u www-data php "$MOODLE_DIR/public/admin/tool/replace/cli/replace.php" \
        --search="http://$ACTUAL:8080" --replace="http://$NUEVO:8080" --shorten --non-interactive >/dev/null
    sudo -u www-data php "$MOODLE_DIR/admin/cli/purge_caches.php"
fi

# Portal
sed -i "s|^APP_URL=.*|APP_URL=http://$NUEVO:8080|; s|^MOODLE_URL=.*|MOODLE_URL=http://$NUEVO:8081|" "$REPO_DIR/.env"
cd "$REPO_DIR"
docker compose up -d --force-recreate app web

echo "Listo:"
echo "  Moodle: http://$NUEVO:8081"
echo "  Portal: http://$NUEVO:8080"
