#!/usr/bin/env bash
set -e

APP_DIR="/var/www/taskora"
PHP_FPM_SERVICE="${PHP_FPM_SERVICE:-}"
NGINX_SERVICE="${NGINX_SERVICE:-nginx}"

if [ -z "$PHP_FPM_SERVICE" ]; then
    for service in php8.5-fpm php8.4-fpm php8.3-fpm php8.2-fpm php-fpm; do
        if systemctl list-unit-files "${service}.service" --no-pager --no-legend | grep -q "^${service}.service"; then
            PHP_FPM_SERVICE="$service"
            break
        fi
    done
fi

if [ -z "$PHP_FPM_SERVICE" ]; then
    echo "PHP-FPM service was not found. Set PHP_FPM_SERVICE before deploy."
    exit 1
fi

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
