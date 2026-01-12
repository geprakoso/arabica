#!/bin/bash

# Araabica Deployment Script
# Usage: ./deploy.sh
# 
# Security Note: Ensure this script is executable only by authorized users.
# Recommended permission: chmod 700 deploy.sh

echo "🚀 Starting Deployment..."

# 1. Pull latest changes
# This fetches the latest code from the main branch.
echo "📥 Pulling latest changes from git..."
git pull origin main

# 2. Build and start containers
# We use --remove-orphans to clean up any old containers that are no longer in the compose file.
echo "🐳 Building and starting Docker containers..."
docker compose up -d --build --remove-orphans

# 3. Install Dependencies
# We run this inside the container to ensure compatibility with the container's PHP version.
echo "📦 Installing Dependencies..."
docker compose exec app composer install --no-dev --optimize-autoloader --no-scripts

# 4. Optimize Laravel
# These commands cache configuration and routes for better production performance.
echo "🧹 Optimizing Laravel application..."
docker compose exec app php artisan optimize:clear
docker compose exec app php artisan optimize
docker compose exec app php artisan view:cache
docker compose exec app php artisan config:cache
docker compose exec app php artisan event:cache

# 5. Run Migrations
# The --force flag is required to run migrations in production mode.
echo "📦 Running database migrations..."
docker compose exec app php artisan migrate --force

# 6. Storage Linking & Permissions
# Ensure the public link exists for file access.
echo "🔗 Linking storage..."
docker compose exec app php artisan storage:link

# Security Hardening: Set ownership to www-data (standard web user)
# This prevents permission issues with uploads and logs.
echo "🔒 Setting file permissions..."
docker compose exec app chown -R www-data:www-data /var/www/storage /var/www/bootstrap/cache

# 7. Restart Queue Worker
# Essential to pick up code changes for background jobs.
echo "🔄 Restarting Queue Worker..."
docker compose exec app php artisan queue:restart

echo "✅ Deployment Completed Successfully!"
