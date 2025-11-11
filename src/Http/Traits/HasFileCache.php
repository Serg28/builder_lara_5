<?php

namespace Vis\Builder\Http\Traits;

use Illuminate\Support\Facades\Storage;

/**
 * Трейт для кеширования различных коллекций моделей в файлы.
 * Поддерживает несколько наборов данных, каждый с собственным тегом.
 *
 * ✅ Как использовать:
 * 1. Подключите трейт в вашу модель: `use HasFileCache;`
 * 2. Переопределите метод getFileCacheDefinitions(), где задаются:
 *    - тег (например, 'default', 'short', 'grouped')
 *    - имя файла кеша (опционально)
 *    - колбэк для выборки данных
 * 3. Получение кеша: `static::getTaggedCache('short')`
 * 4. Обновление всех кешей: `static::updateFileCache()`
 */
trait HasFileCache
{
    protected static array $tableNameCache = [];

    /**
     * Папка хранения кеша
     */
    protected static function getFileCacheDir(): string
    {
        return 'model_cache';
    }

    /**
     * Определение всех кешей по тегам
     * Пример переопределения в модели:a
     *
     * protected static function getFileCacheDefinitions(): array {
     *   return [
     *     'default' => [
     *         'name' => 'cities_' . App::getLocale(),
     *         'query' => fn () => static::query()->where('is_active', 1)->get(['id', 'title']),
     *     ],
     *     'short' => [
     *         'name' => 'cities_short_' . App::getLocale(),
     *         'query' => fn () => static::query()->pluck('title', 'id'),
     *     ],
     *   ];
     * }
     */
    protected static function getFileCacheDefinitions(): array
    {
        return [
            'default' => [
                'name' => static::getFileCacheName(),
                'query' => fn () => static::query()->get(),
            ],
        ];
    }

    /**
     * Имя кеш-файла по умолчанию
     */
    protected static function getFileCacheName($lang = null): string
    {
        $lang = $lang ?: app()->getLocale();
        $class = static::class;
        if (! isset(self::$tableNameCache[$class])) {
            self::$tableNameCache[$class] = with(new static)->getTable();
        }

        return self::$tableNameCache[$class].'_'.$lang;
    }

    /**
     * Путь до кеш-файла по тегу и языку
     */
    protected static function getFileCachePathForTag(string $tag, ?string $lang = null): string
    {
        $lang = $lang ?: app()->getLocale();
        $defs = static::getFileCacheDefinitions();

        if (! isset($defs[$tag])) {
            throw new \InvalidArgumentException("Неизвестный тег кеша: $tag");
        }

        $name = $defs[$tag]['name'] ?? $tag;

        return static::getFileCacheDir()."/{$name}_{$lang}.json";
    }

    /**
     * Получить кешированные данные по тегу
     */
    public static function getTaggedCache(string $tag): \Illuminate\Support\Collection
    {
        $path = static::getFileCachePathForTag($tag);
        if (! Storage::exists($path)) {
            static::updateFileCacheByTag($tag);
        }

        return collect(
            json_decode(Storage::get($path)) ?: []
        );
    }

    /**
     * Обновить кеш по конкретному тегу
     */
    public static function updateFileCacheByTag(string $tag): void
    {
        $defs = static::getFileCacheDefinitions();

        if (! isset($defs[$tag])) {
            throw new \InvalidArgumentException("Неизвестный тег кеша: $tag");
        }

        $queryCallback = $defs[$tag]['query'] ?? null;

        if (! is_callable($queryCallback)) {
            throw new \LogicException("Не определён колбэк запроса для тега: $tag");
        }

        $originalLocale = app()->getLocale();

        foreach (languagesOfSite() as $lang) {
            app()->setLocale($lang);

            $collection = collect(call_user_func($queryCallback));

            $path = static::getFileCachePathForTag($tag, $lang);

            // Если есть данные — сохраняем, иначе удаляем файл
            $collection->isNotEmpty()
                ? Storage::put($path, $collection->toJson(JSON_UNESCAPED_UNICODE))
                : Storage::delete($path);
        }

        app()->setLocale($originalLocale);
    }

    /**
     * Обновить кеши по всем тегам
     */
    public static function updateFileCache(): void
    {
        foreach (array_keys(static::getFileCacheDefinitions()) as $tag) {
            static::updateFileCacheByTag($tag);
        }
    }

    /**
     * Очистить все кеш-файлы
     */
    public static function clearFileCache(): void
    {
        foreach (array_keys(static::getFileCacheDefinitions()) as $tag) {
            foreach (languagesOfSite() as $lang) {
                $path = static::getFileCachePathForTag($tag, $lang);
                Storage::delete($path);
            }
        }
    }

    /**
     * Автоматическое обновление кеша при изменении модели
     */
    protected static function bootHasFileCache(): void
    {
        static::created(fn () => static::updateFileCache());
        static::updated(fn () => static::updateFileCache());
        static::deleted(fn () => static::updateFileCache());
    }
}
