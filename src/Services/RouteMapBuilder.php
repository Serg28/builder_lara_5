<?php

namespace Vis\Builder\Services;

use Illuminate\Support\Facades\Cache;

/**
 * Сервіс для побудови та оновлення кешу прероутера.
 * Відповідає за створення мапи URL -> ID/Controller для швидкого доступу.
 */
class RouteMapBuilder
{
    /**
     * Перебудувати всі карти маршрутів.
     */
    public function rebuildAll(): void
    {
        $this->clear();

        $config = config('prerouter.sources');

        if ($config['tree'] ?? false) {
            $this->buildTree();
        }

        if ($config['category'] ?? false) {
            $this->buildCategories();
        }

        if ($config['product'] ?? false) {
            $this->buildProducts();
        }

        if ($config['news'] ?? false) {
            $this->buildNews();
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
        $locales = languagesOfSite();
        
        $treeModel = config('prerouter.models.tree');
        
        if (!$treeModel || !class_exists($treeModel)) {
            return;
        }

        $treeModel::active()
            ->get(['id', 'slug', 'is_active', 'template'])
            ->each(function ($item) use ($locales) {
                $this->rebuildTreeNode($item, $locales);
            });
    }

    /**
     * Перебудувати кеш для одного вузла Tree.
     * 
     * @param mixed $item Tree model instance
     * @param mixed $locales Колекція або масив кодів мов
     */
    public function rebuildTreeNode($item, $locales = null): void
    {
        $locales = $locales ?? languagesOfSite();

        // 1. Визначаємо контролер та метод на основі шаблону
        $templatesClass = config('prerouter.tree_templates_class');
        
        if (!$templatesClass || !class_exists($templatesClass)) {
            return;
        }

        static $templates = null;
        if ($templates === null) {
            $templates = (new $templatesClass())->templates();
        }

        $templateClass = $templates[$item->template] ?? null;
        
        // Якщо шаблону немає або він некоректний - пропускаємо
        if (! $templateClass || ! class_exists($templateClass)) {
            return;
        }

        try {
            $templateInstance = new $templateClass();
            $action = $templateInstance->getAction(); // Наприклад: "HomeController@index"
            
            if (!str_contains($action, '@')) {
                return;
            }

            [$controllerName, $method] = explode('@', $action);
            
            // CMS передбачає, що контролери знаходяться в App\Http\Controllers
            if (str_starts_with($controllerName, 'App\\')) {
                $controllerClass = $controllerName;
            } else {
                $controllerClass = 'App\\Http\\Controllers\\' . $controllerName;
            }

        } catch (\Exception $e) {
            return;
        }

        // 2. Зберігаємо готовий маршрут для кожної локалі
        foreach ($locales as $locale) {
            $url = urlPathWithoutLocale($item->getUrl($locale));
            
            if (empty($url)) {
                $url = '/';
            }

            $key = "preroute:tree:{$url}";
            
            // Зберігаємо масив даних
            $data = [
                'id' => $item->id,
                'controller' => $controllerClass,
                'method' => $method,
            ];

            Cache::tags(['prerouter', 'tree'])
                ->put($key, $data, now()->addDays(30));
        }
    }

    /**
     * Видалити вузол Tree з кешу.
     * 
     * @param mixed $item Tree model instance
     * @param mixed $locales Колекція або масив кодів мов
     */
    public function deleteTreeNode($item, $locales = null): void
    {
        $locales = $locales ?? languagesOfSite();

        foreach ($locales as $locale) {
            $url = urlPathWithoutLocale($item->getUrl($locale));
            
            if (empty($url)) {
                $url = '/';
            }

            Cache::tags(['prerouter', 'tree'])
                ->forget("preroute:tree:{$url}");
        }
    }

    /**
     * Построить карту для категорий
     */
    public function buildCategories(): void
    {
        $categoryModel = config('prerouter.models.category');
        
        if (!$categoryModel || !class_exists($categoryModel)) {
            return;
        }

        $categoryModel::all(['id', 'slug', 'full_path'])
            ->each(function ($cat) {
                Cache::tags(['prerouter', 'category'])
                    ->forever("preroute:category:{$cat->full_path}", $cat->id);
            });
    }

    /**
     * Построить карту для товаров
     */
    public function buildProducts(): void
    {
        $productModel = config('prerouter.models.product');
        
        if (!$productModel || !class_exists($productModel)) {
            return;
        }

        $productModel::all(['id', 'slug', 'url'])
            ->each(function ($product) {
                Cache::tags(['prerouter', 'product'])
                    ->forever("preroute:product:{$product->url}", $product->id);
            });
    }

    /**
     * Построить карту для новостей
     */
    public function buildNews(): void
    {
        $newsModel = config('prerouter.models.news');
        
        if (!$newsModel || !class_exists($newsModel)) {
            return;
        }

        $newsModel::all(['id', 'slug'])
            ->each(function ($news) {
                Cache::tags(['prerouter', 'news'])
                    ->forever("preroute:news:news/{$news->slug}", $news->id);
            });
    }
}
