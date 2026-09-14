#!/bin/sh
set -e

cd /var/www/html

# Free-tier storage is ephemeral: the SQLite file doesn't survive a restart or
# redeploy, so every boot rebuilds it from migrations + the demo seeder. That
# also means the app always starts from the same known-good demo state.
mkdir -p database
: > database/database.sqlite

php artisan migrate:fresh --seed --force

php artisan config:cache
php artisan route:cache
php artisan view:cache

exec php artisan serve --host=0.0.0.0 --port="${PORT:-10000}"
