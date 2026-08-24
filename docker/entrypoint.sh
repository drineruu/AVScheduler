#!/bin/bash
set -euo pipefail

cd /var/www/html

if [ ! -f artisan ]; then
    exec "$@"
fi

if [ ! -f .env ] && [ -f .env.example ]; then
    cp .env.example .env
fi

if [ ! -d vendor ]; then
    composer install --no-interaction --prefer-dist
fi

if [ -f package.json ] && [ ! -d node_modules ]; then
    npm install
fi

if [ -f .env ]; then
    app_key="$(grep -E '^APP_KEY=' .env | cut -d= -f2- || true)"
    if [ -z "${app_key}" ] || [ "${app_key}" = '""' ] || [ "${app_key}" = "null" ]; then
        php artisan key:generate --force --no-interaction
    fi
fi

if [ -f package.json ] && [ ! -d public/build ]; then
    npm run build
fi

exec "$@"
