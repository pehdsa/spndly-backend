#!/bin/bash
set -e

# --- Wait for Postgres ---
if [ -n "$DB_HOST" ]; then
    echo "[entrypoint] Waiting for Postgres at $DB_HOST:${DB_PORT:-5432}..."
    until nc -z "$DB_HOST" "${DB_PORT:-5432}" 2>/dev/null; do
        sleep 1
    done
    echo "[entrypoint] Postgres is ready."
fi

# --- Cache config, routes, and views (requires env vars at runtime) ---
echo "[entrypoint] Caching config, routes, and views..."
php artisan config:cache
php artisan route:cache
php artisan view:cache

# --- Run migrations ---
echo "[entrypoint] Running migrations..."
php artisan migrate --force --no-interaction

# --- Execute CMD ---
exec "$@"
