#!/bin/bash

# Araabica Deployment Script
# Usage: ./deploy.sh

set -e  # Exit immediately if a command exits with a non-zero status

echo "🚀 Starting Deployment..."

# 1. Pull latest changes
echo "📥 Pulling latest changes from git..."
git pull origin main

# 2. Build and start containers (without local db)
echo "🐳 Building and starting Docker containers..."
docker compose up -d --build --remove-orphans

# 3. Install Dependencies (Ensure vendor is synced)
echo "📦 Installing Dependencies..."
docker compose exec app composer install --no-dev --optimize-autoloader --no-scripts

# 4. Ensure storage link exists
echo "🔗 Creating storage link..."
docker compose exec app php artisan storage:link --force 2>/dev/null || true

# 5. Fix permissions for storage and cache
echo "🔒 Fixing storage and cache permissions..."
docker compose exec app chmod -R 775 storage bootstrap/cache
docker compose exec app chown -R www-data:www-data storage bootstrap/cache

# 6. Optimize Laravel
echo "🧹 Optimizing Laravel application..."
docker compose exec app php artisan optimize:clear
docker compose exec app php artisan optimize
docker compose exec app php artisan view:cache
docker compose exec app php artisan config:cache

# 7. Run Migrations
echo "📦 Running database migrations..."
docker compose exec app php artisan migrate --force

# 7b. Fix Avatar Locations (Private -> Public)
echo "🖼️  Fixing Avatar Locations..."
docker compose exec app php artisan fix:publish-avatars

# 8. Publish Filament assets (if updated)
echo "🎨 Publishing Filament assets..."
docker compose exec app php artisan filament:assets

# 9. Restart Queue Worker
echo "🔄 Restarting Queue Worker..."
docker compose exec app php artisan queue:restart

echo "✅ Deployment Completed Successfully!"
echo ""
echo "💡 To connect to the container shell, run:"
echo "   docker compose exec app bash"
