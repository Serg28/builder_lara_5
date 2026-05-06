#!/bin/bash

# Laravel 11 & 12 Upgrade Script for Builder CMS
# This script helps automate the upgrade process

echo "🚀 Starting Laravel 11 & 12 upgrade process for Builder CMS..."

# Check PHP version
PHP_VERSION=$(php -r "echo PHP_VERSION;")
echo "📋 Current PHP version: $PHP_VERSION"

if ! php -r "exit(version_compare(PHP_VERSION, '8.2.0', '>=') ? 0 : 1);"; then
    echo "❌ Error: PHP 8.2 or higher is required. Current version: $PHP_VERSION"
    exit 1
fi

echo "✅ PHP version check passed"

# Check if composer.json exists
if [ ! -f "composer.json" ]; then
    echo "❌ Error: composer.json not found. Please run this script from your Laravel project root."
    exit 1
fi

echo "📦 Updating composer dependencies..."

# Update composer.json for Laravel 11 & 12
composer require "vis/builder_lara_5:^4.0" --no-update

# Update Laravel framework
composer require "laravel/framework:^11.0|^12.0" --no-update

# Update other dependencies
composer require "cartalyst/sentinel:^8.0" --no-update
composer require "intervention/image:^3.0" --no-update
composer require "predis/predis:^2.0" --no-update
composer require "barryvdh/laravel-debugbar:^3.13" --no-update

echo "🔄 Running composer update..."
composer update

if [ $? -ne 0 ]; then
    echo "❌ Error: Composer update failed. Please resolve dependency conflicts manually."
    exit 1
fi

echo "✅ Composer dependencies updated successfully"

# Publish configuration files
echo "📁 Publishing configuration files..."
php artisan vendor:publish --tag=builder --force

# Publish assets
echo "🎨 Publishing assets..."
php artisan vendor:publish --tag=public --provider="Vis\Builder\BuilderServiceProvider" --force

# Clear caches
echo "🧹 Clearing caches..."
php artisan config:clear
php artisan cache:clear
php artisan view:clear
php artisan route:clear

# Run migrations if needed
echo "🗃️  Running migrations..."
php artisan migrate --force

echo "✅ Laravel 11 & 12 upgrade completed successfully!"
echo ""
echo "📚 Next steps:"
echo "1. Review the LARAVEL_11_12_COMPATIBILITY.md file for detailed changes"
echo "2. Check INTERVENTION_IMAGE_UPGRADE.md if you use custom image processing"
echo "3. Test your application thoroughly"
echo "4. Update any custom code that may be affected by the dependency updates"
echo ""
echo "🎉 Happy coding with Laravel 11 & 12!"