#!/bin/bash
set -e

echo "🔄 Starting application update..."

# 1. Masuk ke mode maintenance
php artisan down || true

# 2. Tarik kode terbaru
echo "📥 Pulling latest code from Git..."
# Reset auto-generated files to avoid conflicts during pull
git checkout CHANGELOG.md VERSION
git pull origin main

# 3. Install/Update dependensi
echo "📦 Updating dependencies..."
composer install --no-dev --optimize-autoloader

# 4. Migrasi database
echo "🗄️ Running database migrations..."
php artisan migrate --force

# 5. Update Changelog di Web UI
echo "📝 Refreshing changelog..."
php artisan app:generate-changelog

# 6. Build frontend assets
echo "🎨 Rebuilding assets..."
npm install
npm run build

# 7. Optimasi Cache
echo "⚡ Clearing and caching configurations..."
php artisan config:cache
php artisan route:cache
php artisan view:cache
php artisan event:cache

# 8. Reset permissions
echo "🔐 Fixing permissions for www-data..."
sudo chown -R www-data:www-data storage bootstrap/cache
sudo chmod -R 775 storage bootstrap/cache

# 9. Restart Horizon (Reload code)
echo "🌅 Restarting Laravel Horizon..."
php artisan horizon:terminate || true

# 10. Selesai
php artisan up

echo "✅ Update finished successfully!"
