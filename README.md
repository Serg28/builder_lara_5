[![StyleCI](https://styleci.io/repos/55775729/shield?branch=master)](https://styleci.io/repos/55775729)

# Builder CMS - Laravel 11 & 12 Support

A powerful CMS package for Laravel applications with support for Laravel 11 and 12.

## Requirements
- PHP 8.2 or higher
- Laravel 11.0+ or Laravel 12.0+

## Installation

### For Laravel 11 & 12 (Stable Release)
```json
{
    "repositories": [
        {
            "type": "vcs",
            "url": "https://github.com/Serg28/builder_lara_5"
        }
    ],
    "require": {
        "vis/builder_lara_5": "^4.0"
    }
}
```

### For Laravel 11 & 12 (Development Branch)
```json
{
    "repositories": [
        {
            "type": "vcs",
            "url": "https://github.com/Serg28/builder_lara_5"
        }
    ],
    "require": {
        "vis/builder_lara_5": "dev-laravel-11-12-support"
    }
}
```

### For older Laravel versions
```bash
composer require "vis/builder_lara_5":"3.*"
```

## Setup

Install the CMS
```bash
php artisan admin:install
```

Generate a password for admin
```bash
php artisan admin:generatePassword
```

Publish vendor assets
```bash
php artisan vendor:publish --tag=public --force --provider="Vis\Builder\BuilderServiceProvider"
```

## Laravel 11 & 12 Compatibility

This version includes:
- Updated dependencies for Laravel 11 & 12
- Enhanced service provider compatibility
- Improved middleware registration
- Better error handling

For detailed compatibility information, see [LARAVEL_11_12_COMPATIBILITY.md](LARAVEL_11_12_COMPATIBILITY.md)

## Version Information

- **v4.0.0**: Laravel 11 & 12 support (stable release)
- **v3.x**: Laravel 5-10 support (maintenance)

For release usage guide, see [RELEASE_USAGE_GUIDE.md](RELEASE_USAGE_GUIDE.md)
For branch usage guide, see [BRANCH_USAGE_GUIDE.md](BRANCH_USAGE_GUIDE.md)


