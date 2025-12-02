# PreRouter - Швидкий старт

## Що це?

Система швидкої маршрутизації через Redis. Замість пошуку сторінки в БД на кожному запиті — миттєвий lookup у кеші.

**Результат:** Швидкість ↑ у 2-5 разів, навантаження на БД ↓ на 90%.

## Швидкий старт

### 1. Увімкнути

```env
PREROUTER_ENABLED=true
```

### 2. Опублікувати конфіг

```bash
php artisan vendor:publish --tag=prerouter-config
```

### 3. Налаштувати `config/prerouter.php`

```php
'tree_templates_class' => \App\Cms\Tree\Tree::class,
'models' => [
    'tree' => \App\Models\Tree::class,
],
```

### 4. Створити Observer

```php
// app/Observers/TreeObserver.php
use Vis\Builder\Services\RouteMapBuilder;

class TreeObserver
{
    public function __construct(protected RouteMapBuilder $routeMapBuilder) {}
    
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
}
```

Зареєструвати в `EventServiceProvider`:
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

### 5. Побудувати кеш

```bash
php artisan prerouter:build
```

## Вимкнення

```env
PREROUTER_ENABLED=false
```

```bash
php artisan config:clear
```

Автоматично повернеться стандартний роутинг.

## Детальна документація

Дивіться [PreRouter.md](PreRouter.md)
