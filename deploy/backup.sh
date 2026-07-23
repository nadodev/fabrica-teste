#!/bin/sh
set -eu

cd "$(dirname "$0")/.."

timestamp="$(date -u +%Y%m%dT%H%M%SZ)"
mkdir -p backups

docker compose --env-file .env.production -f compose.production.yml exec -T mysql \
    sh -c 'exec mysqldump --single-transaction --quick --lock-tables=false -uroot -p"$MYSQL_ROOT_PASSWORD" "$MYSQL_DATABASE"' \
    | gzip -9 > "backups/database-${timestamp}.sql.gz"

docker run --rm \
    -v fabrica-loja_app_storage:/source:ro \
    -v "$(pwd)/backups:/backup" \
    alpine:3.21 \
    tar -czf "/backup/storage-${timestamp}.tar.gz" -C /source .

printf 'Backup concluído: %s\n' "$timestamp"

