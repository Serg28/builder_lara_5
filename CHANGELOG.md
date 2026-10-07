# Changelog

All notable changes to this project will be documented in this file.

## [4.0.0] - 2025-06-13

### Added
- Laravel 11 support
- Laravel 12 support
- PHP 8.2+ requirement
- Enhanced error handling in ServiceProvider
- Compatibility documentation
- Intervention Image 3.0 upgrade guide

### Changed
- **BREAKING**: Minimum PHP version is now 8.2
- **BREAKING**: Updated to Intervention Image 3.0
- **BREAKING**: Updated to Cartalyst Sentinel 8.0
- **BREAKING**: Updated to Predis 2.0
- Updated Laravel framework requirement to ^11.0|^12.0
- Improved ServiceProvider with better error handling
- Enhanced middleware registration
- Updated README with Laravel 11 & 12 information

### Dependencies Updated
- `laravel/framework`: ^11.0|^12.0
- `cartalyst/sentinel`: ^8.0
- `intervention/image`: ^3.0
- `predis/predis`: ^2.0
- `barryvdh/laravel-debugbar`: ^3.13
- `kalnoy/nestedset`: ^6.0|^7.0
- `venturecraft/revisionable`: ^1.0
- `maatwebsite/excel`: ^3.1

### Fixed
- Improved compatibility with modern Laravel versions
- Better middleware handling
- Enhanced service provider registration

### Migration Guide
See [LARAVEL_11_12_COMPATIBILITY.md](LARAVEL_11_12_COMPATIBILITY.md) for detailed migration instructions.

## [3.x] - Previous Versions
- Support for Laravel 5.x - 10.x
- PHP 7.x - 8.1 support
- Intervention Image 2.x