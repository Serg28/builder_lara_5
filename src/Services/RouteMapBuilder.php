<?php

namespace Vis\Builder\Services;

use Illuminate\Support\Facades\Cache;

/**
 * Сервіс для побудови та оновлення кешу прероутера.
 * Відповідає за створення мапи URL -> ID/Controller для швидкого доступу.
 * 
 * Версія Lite: тільки для Tree.
 */
class RouteMapBuilder
{
    /**
     * Конфігурація прероутера.
     */
    protected array $config;

    public function __construct()
    {
        $this->config = config('prerouter');
    }

    /**
     * Перебудувати всі карти маршрутів.
     */
    public function rebuildAll(): void
    {
        $this->clear();

        if ($this->config['sources']['tree'] ?? false) {
            $this->buildTree();
        }
    }

    /**
     * Очистити весь кеш прероутера.
     */
    public function clear(): void
    {
        Cache::tags(['prerouter'])->flush();
    }

    /**
     * Побудувати карту для сторінок (Tree).
     */
    public function buildTree(): void
    {
        $modelClass = $this->config['models']['tree'] ?? null;
        
        if (! $this->isValidModel($modelClass)) {
            return;
        }

        $locales = languagesOfSite();

        $modelClass::active()
            ->get(['id', 'slug', 'is_active', 'template'])
            ->each(fn ($item) => $this->rebuildTreeNode($item, $locales));
    }

    /**
     * Перебудувати кеш для одного вузла Tree.
     * 
     * @param mixed $item Модель Tree
     * @param mixed $locales Колекція або масив кодів мов
     */
    public function rebuildTreeNode($item, $locales = null): void
    {
        $locales = $locales ?? languagesOfSite();
        $controllerInfo = $this->resolveController($item);
        
        if (! $controllerInfo) {
            return;
        }

        foreach ($locales as $locale) {
            $url = urlPathWithoutLocale($item->getUrl($locale));
            
            // Захист від пустих URL - головна сторінка
            if (empty($url)) {
                $url = '/';
            }

            $key = "preroute:tree:{$url}";
            
            $data = [
                'id' => $item->id,
                'controller' => $controllerInfo['class'],
                'method' => $controllerInfo['method'],
            ];

            Cache::tags(['prerouter', 'tree'])
                ->put($key, $data, now()->addDays(30));
        }
    }

    /**
     * Видалити вузол Tree з кешу.
     */
    public function deleteTreeNode($item, $locales = null): void
    {
        $locales = $locales ?? languagesOfSite();

        foreach ($locales as $locale) {
            $url = urlPathWithoutLocale($item->getUrl($locale));
            
            if (empty($url)) {
                $url = '/';
            }

            Cache::tags(['prerouter', 'tree'])->forget("preroute:tree:{$url}");
        }
    }

    /**
     * Визначає контролер та метод для вузла Tree.
     */
    protected function resolveController($item): ?array
    {
        $templatesClass = $this->config['tree_templates_class'] ?? null;
        
        if (! $templatesClass || ! class_exists($templatesClass)) {
            return null;
        }

        // Кешування списку шаблонів у стартичній змінній для продуктивності
        static $templates = null;
        if ($templates === null) {
            $templates = (new $templatesClass())->templates();
        }

        $templateClass = $templates[$item->template] ?? null;

        if (! $templateClass || ! class_exists($templateClass)) {
            return null;
        }

        try {
            $action = (new $templateClass())->getAction();
            
            if (! str_contains($action, '@')) {
                return null;
            }

            [$controllerName, $method] = explode('@', $action);
            
            $controllerClass = str_starts_with($controllerName, 'App\\')
                ? $controllerName
                : 'App\\Http\\Controllers\\' . $controllerName;

            return ['class' => $controllerClass, 'method' => $method];

        } catch (\Exception $e) {
            return null;
        }
    }

    /**
     * Перевірка валідності моделі.
     */
    protected function isValidModel(?string $class): bool
    {
        return $class && class_exists($class);
    }
}
