# PreRouter - Швидка маршрутизація через Redis

## Що це?

**PreRouter** — це система швидкої маршрутизації для динамічних сторінок сайту (Tree, новини, категорії, товари). Замість того, щоб на кожному запиті шукати сторінку в базі даних, система зберігає мапу URL → Контролер у Redis і миттєво знаходить потрібний обробник.

## Яку проблему вирішує?

### Проблема (без PreRouter):
На кожному запиті Laravel:
1. Шукає відповідність URL у таблиці моделі Tree → сторінка
2. Визначає контролер і метод
3. Викликає контролер
4. При відсутності сторінки в базі даних викликається стандартний роутинг Laravel

**Результат:** Повільна робота при великій кількості запитів і зайві запити для пошуку сторінок, які не відносяться до динамічних сторінок, наприклад, статті, новини, категорії, товари. Навіть службові виклики livewire/update або api/... викликають зайві запити до бази даних.

### Рішення (з PreRouter):
1. Один раз будується кеш: URL → {id, controller, method}
2. На кожному запиті: Redis lookup (< 1ms)
3. Миттєвий виклик контролера

**Результат:** Швидкість зросла в 2-5 разів, навантаження на БД знизилось на 90%.

## Як це працює?

```
Запит → PreRouter Middleware → Redis lookup → Контролер
                ↓ (якщо не знайдено)
           Стандартний роутинг Laravel
```

### Автоматичне оновлення кешу

**TreeObserver** відстежує зміни сторінок:
- Створення → додає в кеш
- Оновлення → оновлює кеш
- Видалення → видаляє з кешу

**Автоперегенерація:**
```bash
php artisan cache:clear
# Автоматично викликається prerouter:build
```

## Використання

### 1. Увімкнення

У `.env`:
```env
PREROUTER_ENABLED=true
```

### 2. Налаштування (опціонально)

Опублікувати конфіг:
```bash
php artisan vendor:publish --tag=prerouter-config
```

Відредагувати `config/prerouter.php`:
```php
return [
    'enabled' => env('PREROUTER_ENABLED', true),
    
    // Класс для отримання шаблонів Tree
    'tree_templates_class' => \App\Cms\Tree\Tree::class,
    
    // Які джерела обробляти
    'sources' => [
        'tree'     => true,   // Сторінки
        'news'     => false,  // Новини
        'category' => false,  // Категорії
        'product'  => false,  // Товари
    ],
    
    // Моделі
    'models' => [
        'tree' => \App\Models\Tree::class,
        // ...
    ],
];
```

### 3. Створення Observer (для Tree)

```php
// app/Observers/TreeObserver.php
namespace App\Observers;

use App\Models\Tree;
use Vis\Builder\Services\RouteMapBuilder;

class TreeObserver
{
    protected RouteMapBuilder $routeMapBuilder;

    public function __construct(RouteMapBuilder $routeMapBuilder)
    {
        $this->routeMapBuilder = $routeMapBuilder;
    }

    public function created(Tree $tree): void
    {
        $this->routeMapBuilder->rebuildTreeNode($tree);
    }

    public function updated(Tree $tree): void
    {
        if ($tree->wasChanged(['slug', 'url', 'is_active', 'template'])) {
            $this->routeMapBuilder->deleteTreeNode($tree);
            $this->routeMapBuilder->rebuildTreeNode($tree);
        }
    }

    public function deleted(Tree $tree): void
    {
        $this->routeMapBuilder->deleteTreeNode($tree);
    }

    public function restored(Tree $tree): void
    {
        $this->routeMapBuilder->rebuildTreeNode($tree);
    }
}
```

Зареєструвати в `app/Providers/EventServiceProvider.php`:
```php
protected $observers = [
    \App\Models\Tree::class => [\App\Observers\TreeObserver::class],
];
```

або прямо у моделі

```php
use Vis\Builder\Traits\ObservedBy;
use App\Observers\TreeObserver;

#[ObservedBy(TreeObserver::class)]
class Tree extends TreeBuilder
{

}
```

### 4. Побудова кешу

```bash
php artisan prerouter:build
```

або

```bash
php artisan cache:clear
```
автоматично викликається prerouter:build при ввімкненні PreRouter

Або для конкретного типу:
```bash
php artisan prerouter:build --type=tree
php artisan prerouter:build --type=news
```

### 5. Перевірка роботи

```bash
php artisan tinker
>>> Cache::tags(['prerouter', 'tree'])->get('preroute:tree:/')
=> [
     "id" => 1,
     "controller" => "App\Http\Controllers\HomeController",
     "method" => "index",
   ]
```

## Вимкнення PreRouter

### Повне вимкнення

У `.env`:
```env
PREROUTER_ENABLED=false
```

Очистити кеш:
```bash
php artisan config:clear
```

**Результат:**
- Middleware не реєструється
- Команда `prerouter:build` недоступна
- Автоматично вмикається стандартний роутинг через `route_frontend.php`

### Вимкнення автоперегенерації

У `.env`:
```env
PREROUTER_AUTO_REBUILD=false
```

Або вручну:
```bash
php artisan cache:clear --no-preroute
```

## Команди

### Побудова кешу
```bash
php artisan prerouter:build           # Всі джерела
php artisan prerouter:build --type=tree
php artisan prerouter:build --type=news
```

### Очищення кешу
```bash
php artisan cache:clear
# Автоматично перебудовується (якщо PREROUTER_AUTO_REBUILD=true)
```

## Виключення URL

Деякі URL не обробляються PreRouter (налаштовується в конфігу):
```php
'exclude_prefixes' => [
    'admin',      // Адмінка
    'api',        // API
    'livewire',   // Livewire
    'storage',    // Файли
    '_debugbar',  // Debug bar
],
```

## Продуктивність

### До впровадження:
- 100+ SQL-запитів на хіт
- Час відповіді: 200-500ms
- Навантаження на БД: високе

### Після впровадження:
- 1-5 SQL-запитів на хіт
- Час відповіді: 50-100ms
- Навантаження на БД: мінімальне
- Redis lookup: < 1ms

## Fallback (аварійне відключення)

Якщо виникли проблеми:

1. Вимкнути в `.env`:
   ```env
   PREROUTER_ENABLED=false
   ```

2. Очистити кеш:
   ```bash
   php artisan config:clear
   ```

Сайт автоматично повернеться до стандартного роутингу.

## Технічні деталі

### Архітектура

```
BuilderServiceProvider
├── registerPreRouter()
│   ├── Реєстрація Middleware (якщо enabled=true)
│   └── Слухач CommandFinished (автоперегенерація)
└── setupRoutes()
    └── Завантаження route_frontend.php (якщо enabled=false)
```

### Компоненти

- **Middleware:** `Vis\Builder\Http\Middleware\CachePreRouter`
- **Service:** `Vis\Builder\Services\RouteMapBuilder`
- **Command:** `Vis\Builder\Console\PreRouterBuild`
- **Config:** `config/prerouter.php`

### Формат кешу

```php
// Ключ
"preroute:tree:/about-us"

// Значення
[
    'id' => 5,
    'controller' => 'App\Http\Controllers\PageController',
    'method' => 'show',
]
```

### Теги кешу

- `prerouter` — загальний тег
- `tree`, `news`, `category`, `product` — теги по типах

Очищення конкретного типу:
```php
Cache::tags(['prerouter', 'tree'])->flush();
```

## Поширені питання

### Чи потрібно вручну перебудовувати кеш?

Ні. Observer автоматично оновлює кеш при змінах через адмінку.

Вручну потрібно тільки якщо:
- Змінили дані напряму в БД
- Додали новий тип джерела
- Після відновлення з бекапу

### Що буде, якщо Redis впаде?

PreRouter пропустить запит далі, і Laravel обробить його через стандартний роутинг. Сайт продовжить працювати.

### Чи можна використовувати без Redis?

Ні. PreRouter використовує `Cache::tags()`, що підтримується тільки Redis і Memcached.

### Як додати підтримку нових типів (новини, товари)?

1. Увімкнути в `config/prerouter.php`:
   ```php
   'sources' => [
       'news' => true,
   ],
   ```

2. Налаштувати модель і контролер:
   ```php
   'models' => [
       'news' => \App\Models\News::class,
   ],
   'controllers' => [
       'news' => [\App\Http\Controllers\NewsController::class, 'show'],
   ],
   ```

3. Створити Observer для автооновлення (опціонально)

4. Побудувати кеш:
   ```bash
   php artisan prerouter:build --type=news
   ```

---

**Версія:** 1.0  
**Пакет:** vis/builder_lara_5  
**Підтримка:** Redis 5.0+, Laravel 11+
