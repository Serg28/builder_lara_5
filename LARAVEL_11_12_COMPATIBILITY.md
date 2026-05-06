# Laravel 11 & 12 Compatibility Guide

## Overview
This version of the Builder CMS package has been updated to support Laravel 11 and 12.

## Requirements
- PHP 8.2 or higher
- Laravel 11.0+ or Laravel 12.0+

## Updated Dependencies
The following dependencies have been updated for Laravel 11 & 12 compatibility:

- `laravel/framework`: ^11.0|^12.0
- `cartalyst/sentinel`: ^8.0
- `intervention/image`: ^3.0
- `predis/predis`: ^2.0
- `barryvdh/laravel-debugbar`: ^3.13

## Breaking Changes
1. **PHP Version**: Minimum PHP version is now 8.2
2. **Intervention Image**: Updated to version 3.0 with new API
3. **Predis**: Updated to version 2.0
4. **Cartalyst Sentinel**: Updated to version 8.0

## Installation

### For Laravel 11
```bash
composer require "vis/builder_lara_5":"^4.0"
```

### For Laravel 12
```bash
composer require "vis/builder_lara_5":"^4.0"
```

## Migration from Previous Versions

### 1. Update Composer Dependencies
```bash
composer update
```

### 2. Update Configuration
If you have published the configuration files, you may need to update them:
```bash
php artisan vendor:publish --tag=builder --force
```

### 3. Run Migrations
```bash
php artisan migrate
```

### 4. Clear Cache
```bash
php artisan config:clear
php artisan cache:clear
php artisan view:clear
```

## New Features
- Enhanced compatibility with Laravel 11 & 12
- Improved error handling
- Better middleware registration
- Updated service provider for modern Laravel versions

## Known Issues
- Some third-party packages may need updates for full Laravel 11/12 compatibility
- Custom middleware may need adjustments

## Support
For issues related to Laravel 11 & 12 compatibility, please create an issue in the repository.