# Intervention Image 3.0 Upgrade Guide

## Overview
This package has been updated to use Intervention Image 3.0, which includes breaking changes from version 2.x.

## Key Changes in Intervention Image 3.0

### 1. New Manager-based Architecture
```php
// Old way (v2.x)
use Intervention\Image\ImageManagerStatic as Image;
Image::make($path)->resize(300, 200)->save();

// New way (v3.x)
use Intervention\Image\ImageManager;
use Intervention\Image\Drivers\Gd\Driver;

$manager = new ImageManager(new Driver());
$image = $manager->read($path);
$image->resize(300, 200)->save();
```

### 2. Configuration Changes
The configuration structure has changed. Update your `config/image.php`:

```php
return [
    'driver' => 'gd', // or 'imagick'
];
```

### 3. Method Changes
Some methods have been renamed or changed:

- `make()` → `read()`
- `canvas()` → `create()`
- `insert()` → `place()`
- `fit()` → `cover()`

### 4. Filter Changes
Filters now use a different syntax:

```php
// Old way
$image->filter(new MyFilter());

// New way
$image->modify(new MyModifier());
```

## Migration Steps

### 1. Update Image Processing Code
If you have custom image processing code, update it to use the new API:

```php
// Before
use Intervention\Image\ImageManagerStatic as Image;

public function processImage($path)
{
    return Image::make($path)
        ->resize(300, 200)
        ->save();
}

// After
use Intervention\Image\ImageManager;
use Intervention\Image\Drivers\Gd\Driver;

public function processImage($path)
{
    $manager = new ImageManager(new Driver());
    return $manager->read($path)
        ->resize(300, 200)
        ->save();
}
```

### 2. Update Configuration
Publish and update the image configuration:

```bash
php artisan vendor:publish --provider="Intervention\Image\ImageServiceProvider"
```

### 3. Clear Cache
After updating, clear all caches:

```bash
php artisan config:clear
php artisan cache:clear
```

## Compatibility Layer
The package includes a compatibility layer to minimize breaking changes, but some manual updates may be required for custom image processing code.

## Resources
- [Intervention Image 3.0 Documentation](https://image.intervention.io/v3)
- [Migration Guide](https://image.intervention.io/v3/introduction/upgrade)