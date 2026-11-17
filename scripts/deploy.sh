#!/usr/bin/env bash
# Actualiza el portal en el servidor con lo último de main.
set -euo pipefail
cd "$(dirname "$0")/.."

if [ ! -f .env ]; then
    echo "Falta el archivo .env. Copia .env.example a .env y llena las claves."
    exit 1
fi
chmod 600 .env

git pull --ff-only
docker compose up -d --build
docker image prune -f
docker compose ps
