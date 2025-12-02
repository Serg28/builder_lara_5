<?php

namespace Vis\Builder\Http\Middleware;

use Closure;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Cache;
use Symfony\Component\HttpFoundation\Response;

/**
 * Middleware для швидкої маршрутизації через Redis (PreRouter).
 * Перехоплює запити до завантаження основного роутера Laravel.
 */
class CachePreRouter
{
    /**
     * Обробка вхідного запиту.
     *
     * @param  \Closure(\Illuminate\Http\Request): (\Symfony\Component\HttpFoundation\Response)  $next
     */
    public function handle(Request $request, Closure $next): ?Response
    {
        $config = config('prerouter');

        if (! ($config['enabled'] ?? false)) {
            return $next($request);
        }

        $path = urlPathWithoutLocale() ?: '/';

        foreach ($config['exclude_prefixes'] as $prefix) {
            if (str_starts_with($path, $prefix)) {
                return $next($request);
            }
        }

        foreach ($config['sources'] as $type => $enabled) {
            if (! $enabled) {
                continue;
            }

            $cache = Cache::tags(['prerouter', $type]);
            $key = "preroute:$type:$path";

            if (! $cache->has($key)) {
                continue;
            }

            $cachedData = $cache->get($key);

            // 1. Обработка Tree (данные в кеше - массив с контроллером)
            if ($type === 'tree' && is_array($cachedData)) {
                // Используем модель из конфига или дефолтную
                $treeModelClass = $config['models']['tree'] ?? \App\Models\Tree::class;
                
                $node = $treeModelClass::find($cachedData['id']);

                if (! $node || $node->is_active === 0) {
                    $cache->forget($key);
                    continue;
                }

                $controller = app($cachedData['controller']);
                $method = $cachedData['method'];

                if (method_exists($controller, 'callAction')) {
                    return response($controller->callAction('init', [$node, $method]));
                }

                return $controller->$method($node);
            }

            // 2. Обработка остальных типов (данные в кеше - ID)
            if (! is_array($cachedData)) {
                $modelClass = $config['models'][$type] ?? null;
                
                if (! $modelClass) {
                    continue;
                }

                $model = $modelClass::find($cachedData);

                if (! $model || (method_exists($model, 'getAttribute') && $model->getAttribute('is_active') === 0)) {
                    $cache->forget($key);
                    continue;
                }

                [$controllerClass, $method] = $config['controllers'][$type];
                
                return app($controllerClass)->$method($model);
            }
        }

        return $next($request);
    }
}
