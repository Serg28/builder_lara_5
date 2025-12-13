<?php

namespace Vis\Builder\Http\Middleware;

use Closure;
use Vis\Builder\Services\RouteMapBuilder;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Cache;
use Symfony\Component\HttpFoundation\Response;

/**
 * Middleware для швидкої маршрутизації через Redis (PreRouter).
 * Перехоплює запити до завантаження основного роутера Laravel.
 *
 * Версія Lite: тільки для Tree (сторінок сайту).
 */
class CachePreRouter
{
    /**
     * Конфігурація прероутера.
     */
    protected array $config;

    /**
     * Конструктор.
     */
    public function __construct()
    {
        $this->config = config('prerouter');
    }

    /**
     * Обробка вхідного запиту.
     */
    public function handle(Request $request, Closure $next): ?Response
    {
        // 1. Перевіряємо, чи увімкнено прероутер
        if (! ($this->config['enabled'] ?? false)) {
            return $next($request);
        }

        $path = urlPathWithoutLocale() ?: '/';

        // 2. Перевіряємо виключення (адмінка, api тощо)
        if ($this->shouldSkip($path)) {
            return $next($request);
        }

        // 3. Перевіряємо джерело Tree (якщо увімкнено)
        if ($this->config['sources']['tree'] ?? false) {
            
            // Отримуємо дані з кешу або бази
            $cachedData = $this->resolveRoute('tree', $path);

            if ($cachedData !== null) {
                // Повертаємо відповідь, якщо знайдено
                return $this->dispatchTreeRoute($cachedData);
            }
        }

        return $next($request);
    }

    /**
     * Перевірка, чи слід пропустити обробку для цього шляху.
     */
    protected function shouldSkip(string $path): bool
    {
        foreach ($this->config['exclude_prefixes'] as $prefix) {
            if (str_starts_with($path, $prefix)) {
                return true;
            }
        }

        return false;
    }

    /**
     * Отримання даних маршруту (з кешу або "лінива" дозагрузка з БД).
     */
    protected function resolveRoute(string $type, string $path): mixed
    {
        $key = "preroute:{$type}:{$path}";
        $tags = ['prerouter', $type];

        // Швидка перевірка в Redis
        if (Cache::tags($tags)->has($key)) {
            return Cache::tags($tags)->get($key);
        }

        // Якщо немає в кеші - шукаємо в БД (Lazy Loading) і кешуємо
        return $this->resolveMiss($path);
    }

    /**
     * Логіка обробки Tree маршруту.
     */
    protected function dispatchTreeRoute(array $data): ?Response
    {
        $modelClass = $this->config['models']['tree'] ?? \App\Models\Tree::class;
        $node = $modelClass::find($data['id']);

        if (! $node || $node->is_active === 0) {
            return null;
        }

        $controller = app($data['controller']);
        $method = $data['method'];

        if (method_exists($controller, 'callAction')) {
            return response($controller->callAction('init', [$node, $method]));
        }

        return $controller->$method($node);
    }

    /**
     * "Лінива" загрузка маршруту з БД при промаху кешу.
     */
    protected function resolveMiss(string $path): mixed
    {
        $modelClass = $this->config['models']['tree'] ?? \App\Models\Tree::class;

        if (! $modelClass || ! class_exists($modelClass)) {
            return null;
        }

        // Визначаємо slug з URL
        $slug = ($path === '/' || $path === '') ? '/' : last(explode('/', trim($path, '/')));

        // Базовий запит
        $query = $modelClass::where('slug', $slug);

        // Враховуємо scopeActive або поле is_active
        if (method_exists($modelClass, 'scopeActive')) {
            $query->active();
        } elseif (method_exists($modelClass, 'getTable') && \Schema::hasColumn((new $modelClass)->getTable(), 'is_active')) {
             $query->where('is_active', 1);
        }

        // Перевіряємо кандидатів
        foreach ($query->get() as $item) {
             if ($this->matchAndCache($item, $path)) {
                 // Повертаємо свіжий кеш
                 $key = "preroute:tree:{$path}";
                 return Cache::tags(['prerouter', 'tree'])->get($key);
             }
        }

        return null;
    }

    /**
     * Перевірка URL моделі та збереження в кеш.
     */
    protected function matchAndCache($item, string $path): bool
    {
         if (! method_exists($item, 'getUrl')) {
             return false;
         }

         $itemUrl = urlPathWithoutLocale($item->getUrl());

         // Нормалізація для порівняння (прибираємо зайві слеші)
         $normalizedItemUrl = trim($itemUrl ?: '/', '/');
         $normalizedPath = trim($path ?: '/', '/');

         if ($normalizedItemUrl === $normalizedPath) {
            // Для Tree використовуємо сервіс для повної перебудови вузла
            (new RouteMapBuilder())->rebuildTreeNode($item);
            return true;
         }

         return false;
    }
}
