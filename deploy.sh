#!/bin/bash

# Araabica Deployment Script
# Usage: ./deploy.sh

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

# 4. Optimize Laravel
echo "🧹 Optimizing Laravel application..."
docker compose exec app php artisan optimize:clear
docker compose exec app php artisan optimize
docker compose exec app php artisan view:cache
docker compose exec app php artisan config:cache

# 4. Run Migrations
echo "📦 Running database migrations..."
docker compose exec app php artisan migrate --force

# 5. Restart Queue Worker
echo "🔄 Restarting Queue Worker..."
docker compose exec app php artisan queue:restart

echo "✅ Deployment Completed Successfully!"
