#!/usr/bin/env bash
set -e

APP_DIR="/var/www/taskora"
PHP_FPM_SERVICE="${PHP_FPM_SERVICE:-php8.4-fpm}"
NGINX_SERVICE="${NGINX_SERVICE:-nginx}"

cd "$APP_DIR"

if [ ! -f ".env" ]; then
    echo "Missing .env. Create it from docs/env.staging.example before deploy."
    exit 1
fi

git pull --ff-only

composer install --no-dev --optimize-autoloader --no-interaction --prefer-dist

if [ -f "package-lock.json" ]; then
    npm ci
else
    npm install
fi

npm run build

php artisan migrate --force
php artisan config:cache

if php artisan route:cache; then
    echo "Routes cached."
else
    echo "Route cache is not supported by the current routes. Clearing route cache and continuing."
    php artisan route:clear
fi

php artisan view:cache
php artisan queue:restart || true

sudo systemctl reload "$PHP_FPM_SERVICE"
sudo systemctl reload "$NGINX_SERVICE"
