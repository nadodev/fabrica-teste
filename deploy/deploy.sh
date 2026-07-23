#!/bin/sh
set -eu

cd "$(dirname "$0")/.."

test -f .env.production || {
    printf 'Arquivo .env.production ausente.\n' >&2
    exit 1
}

if [ -n "$(docker compose --env-file .env.production -f compose.production.yml ps -q mysql 2>/dev/null)" ]; then
    sh deploy/backup.sh
else
    printf 'Primeira implantação: banco ainda não iniciado, backup ignorado.\n'
fi
git pull --ff-only origin main
docker compose --env-file .env.production -f compose.production.yml build --pull
docker compose --env-file .env.production -f compose.production.yml up -d --remove-orphans
docker compose --env-file .env.production -f compose.production.yml ps
