<?php

namespace Vis\Builder\Observers;

use Vis\Builder\Services\RouteMapBuilder;

/**
 * Спостерігач для моделі Tree (автоматичне оновлення прероутера).
 */
class PreRouterTreeObserver
{
    private RouteMapBuilder $builder;

    public function __construct(RouteMapBuilder $builder)
    {
        $this->builder = $builder;
    }

    /**
     * Створено нову сторінку.
     */
    public function created($tree): void
    {
        $this->builder->rebuildTreeNode($tree);
    }

    /**
     * Оновлено сторінку.
     */
    public function updated($tree): void
    {
        // Перебудовуємо тільки якщо змінились важливі поля
        if ($this->shouldRebuild($tree)) {
            $this->builder->deleteTreeNode($tree);
            $this->builder->rebuildTreeNode($tree);
        }
    }

    /**
     * Видалено сторінку.
     */
    public function deleted($tree): void
    {
        $this->builder->deleteTreeNode($tree);
    }

    /**
     * Відновлено сторінку.
     */
    public function restored($tree): void
    {
        $this->builder->rebuildTreeNode($tree);
    }

    /**
     * Перевірка, чи потрібно оновлювати кеш прероутера.
     */
    private function shouldRebuild($tree): bool
    {
        // Список полів, що впливають на роутинг
        return $tree->wasChanged([
            'slug', 
            'url',          // Для деяких кастомних реалізацій
            'is_active', 
            'parent_id', 
            'template'
        ]);
    }
}
