#!/bin/bash

set -e

echo "==================================="
echo " Iniciando deploy..."
echo "==================================="

cd /opt/apps/fabrica-loja

echo "Atualizando código..."
git fetch origin
git checkout main
git pull origin main

echo "Recriando containers..."
docker compose \
    --env-file .env.production \
    -f compose.production.yml \
    up -d --build

echo "Executando migrations..."
docker compose \
    --env-file .env.production \
    -f compose.production.yml \
    exec -T app php artisan migrate --force

echo "Otimizando Laravel..."
docker compose \
    --env-file .env.production \
    -f compose.production.yml \
    exec -T app php artisan optimize

echo "Deploy finalizado com sucesso!"
