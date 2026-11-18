#!/bin/sh
# Production entrypoint: warm the caches; the app container also runs migrations.
set -e

if [ "${RUN_MIGRATIONS:-false}" = "true" ]; then
    if [ "${DB_CONNECTION:-sqlite}" = "sqlite" ]; then touch "${DB_DATABASE:-database/database.sqlite}"; fi
    php artisan migrate --force
fi

php artisan optimize

exec "$@"
