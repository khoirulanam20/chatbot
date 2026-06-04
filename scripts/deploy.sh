#!/usr/bin/env bash
# Jalankan di server production setelah git pull.
set -euo pipefail

cd "$(dirname "$0")/.."

echo "==> Composer install (production)..."
composer install --no-dev --prefer-dist --optimize-autoloader --no-interaction

echo "==> Hapus cache bootstrap lama (penting setelah hapus Livewire)..."
rm -f bootstrap/cache/packages.php bootstrap/cache/services.php bootstrap/cache/config.php bootstrap/cache/routes-v7.php 2>/dev/null || true

echo "==> Clear & rebuild Laravel cache..."
php artisan optimize:clear
php artisan package:discover --ansi

echo "==> Frontend build..."
npm ci
npm run build

echo "==> Migrasi..."
php artisan migrate --force

echo "==> Cache production..."
php artisan config:cache
php artisan route:cache
php artisan view:cache

echo "Deploy selesai."
