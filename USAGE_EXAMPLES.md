# Usage Examples for Laravel 11 & 12

## Basic Setup

### 1. Installation
```bash
composer require "vis/builder_lara_5":"^4.0"
```

### 2. Service Provider Registration
The service provider is automatically registered via Laravel's package discovery.

### 3. Configuration Publishing
```bash
php artisan vendor:publish --tag=builder
```

### 4. Asset Publishing
```bash
php artisan vendor:publish --tag=public --provider="Vis\Builder\BuilderServiceProvider"
```

## Laravel 11 Specific Features

### Using the New Application Structure
```php
// In bootstrap/app.php (Laravel 11)
use Illuminate\Foundation\Application;
use Illuminate\Foundation\Configuration\Exceptions;
use Illuminate\Foundation\Configuration\Middleware;

return Application::configure(basePath: dirname(__DIR__))
    ->withRouting(
        web: __DIR__.'/../routes/web.php',
        commands: __DIR__.'/../routes/console.php',
        health: '/up',
    )
    ->withMiddleware(function (Middleware $middleware) {
        // Builder CMS middleware is automatically registered
    })
    ->withExceptions(function (Exceptions $exceptions) {
        //
    })->create();
```

### Using New Artisan Commands
```bash
# Install CMS
php artisan admin:install

# Generate admin password
php artisan admin:generatePassword

# Create configuration
php artisan admin:createConfig

# Create WebP images
php artisan admin:createImgWebp
```

## Laravel 12 Compatibility

### Enhanced Performance Features
The package is optimized for Laravel 12's performance improvements:

```php
// Utilizing Laravel 12's enhanced caching
use Illuminate\Support\Facades\Cache;

class ExampleController extends Controller
{
    public function index()
    {
        $data = Cache::remember('cms_data', 3600, function () {
            return $this->getCmsData();
        });
        
        return view('admin::dashboard', compact('data'));
    }
}
```

## Working with Models

### User Model Example
```php
use Vis\Builder\User;
use Cartalyst\Sentinel\Laravel\Facades\Sentinel;

class UserController extends Controller
{
    public function createUser()
    {
        $user = Sentinel::register([
            'email' => 'user@example.com',
            'password' => 'password',
            'first_name' => 'John',
            'last_name' => 'Doe',
        ]);
        
        return $user;
    }
}
```

### Tree Model Example
```php
use Vis\Builder\Tree;

class ContentController extends Controller
{
    public function getTree()
    {
        $tree = Tree::with('translations')
            ->where('is_active', 1)
            ->get();
            
        return $tree;
    }
}
```

## Image Processing with Intervention Image 3.0

### Basic Image Processing
```php
use Intervention\Image\ImageManager;
use Intervention\Image\Drivers\Gd\Driver;

class ImageController extends Controller
{
    public function processImage($path)
    {
        $manager = new ImageManager(new Driver());
        
        $image = $manager->read($path);
        $image->resize(300, 200);
        $image->save();
        
        return response()->json(['success' => true]);
    }
}
```

### Advanced Image Operations
```php
use Intervention\Image\ImageManager;
use Intervention\Image\Drivers\Imagick\Driver;

class AdvancedImageController extends Controller
{
    public function createThumbnail($path)
    {
        $manager = new ImageManager(new Driver());
        
        $image = $manager->read($path);
        $image->cover(150, 150);
        $image->save(storage_path('app/thumbnails/thumb_' . basename($path)));
        
        return response()->json(['thumbnail_created' => true]);
    }
}
```

## Middleware Usage

### Custom Authentication
```php
use Vis\Builder\Authenticate;

class CustomController extends Controller
{
    public function __construct()
    {
        $this->middleware('auth.admin');
    }
    
    public function adminDashboard()
    {
        return view('admin::dashboard');
    }
}
```

## Translation Features

### Using Translations
```php
use Vis\Builder\TranslationsPhrases;

class TranslationController extends Controller
{
    public function getTranslation($key)
    {
        return __cms($key);
    }
    
    public function getAllTranslations()
    {
        return TranslationsPhrases::fillCacheTrans();
    }
}
```

## Configuration Examples

### Custom Configuration
```php
// config/builder/tb-definitions/example.php
return [
    'db' => [
        'table' => 'example_table',
    ],
    'options' => [
        'caption' => 'Example Management',
        'model' => 'App\Models\Example',
    ],
    'fields' => [
        'name' => [
            'caption' => 'Name',
            'type' => 'text',
            'validate' => 'required|max:255',
        ],
        'description' => [
            'caption' => 'Description',
            'type' => 'textarea',
        ],
    ],
];
```

## Error Handling

### Custom Error Pages
```php
// In your exception handler
public function render($request, Exception $exception)
{
    if ($exception instanceof \Vis\Builder\Exceptions\BuilderException) {
        return response()->view('admin::errors.builder', [
            'exception' => $exception
        ], 500);
    }
    
    return parent::render($request, $exception);
}
```

## Performance Optimization

### Caching Strategies
```php
use Illuminate\Support\Facades\Cache;
use Vis\Builder\Tree;

class OptimizedController extends Controller
{
    public function getCachedTree()
    {
        return Cache::tags(['cms', 'tree'])->remember('tree_structure', 3600, function () {
            return Tree::with('translations')->get();
        });
    }
    
    public function clearCmsCache()
    {
        Cache::tags(['cms'])->flush();
        return response()->json(['cache_cleared' => true]);
    }
}
```