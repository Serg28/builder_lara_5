<?php

namespace Vis\Builder\Http\Traits;

use Illuminate\Support\Facades\Storage;

/**
 * Трейт для кеширования различных коллекций моделей в файлы JSON.
 *
 * Кеширует выборки, минуя стандартный кеш Laravel. И затем используется в методах getCachedCollection() и getTaggedCache()
 *
 * Позволяет определять несколько наборов кешируемых данных (тегов),
 * каждый из которых сохраняется в собственный файл и может иметь
 * индивидуальные правила выборки данных.
 *
 * Поддерживает автоматическую локализацию значений при генерации кеша,
 * а также обновление кешей при создании, изменении и удалении моделей.
 *
 * ---
 * ✅ Как использовать:
 *
 * 1. Подключите трейт в вашу модель:
 *      use HasFileCache;
 *
 * 2. Переопределите метод getFileCacheDefinitions(), где задаются:
 *      - тег (например: 'default', 'short', 'grouped')
 *      - имя файла кеша (опционально)
 *      - колбэк для выборки данных
 *
 * 3. Получение кеша:
 *      static::getTaggedCache('short');
 *
 * 4. Обновление всех кешей:
 *      static::updateFileCache();
 *
 * ---
 * Каждый кеш создаётся отдельно для каждого языка,
 * возвращаемого функцией languagesOfSite().
 *
 *  Пример переопределения в модели:
 *
 * @example
 *
 *  ```php
 *  protected static function getFileCacheDefinitions(): array {
 *    return [
 *      'default' => [
 *          'name' => 'cities_' . App::getLocale(),
 *          'query' => fn () => static::query()->where('is_active', 1)->get(['id', 'title']),
 *      ],
 *      'short' => [
 *          'name' => 'cities_short_' . App::getLocale(),
 *          'query' => fn () => static::query()->pluck('title', 'id'),
 *      ],
 *    ];
 *  }
 * ```
 */
trait HasFileCache
{
    /**
     * Локальный кеш имён таблиц.
     *
     * @var array<class-string, string>
     */
    protected static array $tableNameCache = [];

    /**
     * Возвращает директорию, в которой хранятся кеш-файлы.
     *
     * @return string Путь к директории хранения кеша
     */
    protected static function getFileCacheDir(): string
    {
        return 'model_cache';
    }

    /**
     * Определяет наборы доступных кешей.
     *
     * Каждый элемент массива должен содержать:
     *  - name  — имя файла кеша (опционально)
     *  - query — колбэк выборки данных
     *
     * @return array<string, array{name?: string, query: callable}>
     */
    protected static function getFileCacheDefinitions(): array
    {
        return [
            'default' => [
                'name' => static::getFileCacheName(),
                'query' => fn() => static::query()->get(),
            ],
        ];
    }

    /**
     * Возвращает имя кеш-файла по умолчанию.
     *
     * Формируется на основе названия таблицы и языка.
     *
     * @param string|null $lang Язык (если не указан — текущий)
     * @return string            Имя файла без расширения
     */
    protected static function getFileCacheName(?string $lang = null): string
    {
        $lang = $lang ?: app()->getLocale();
        $class = static::class;

        return self::$tableNameCache[$class]
            ??= with(new static)->getTable() . '_' . $lang;
    }

    /**
     * Строит путь к файлу кеша по тегу и языку.
     *
     * @param string $tag Имя набора кеша
     * @param string|null $lang Язык кеша
     * @return string            Полный путь до JSON-файла
     *
     * @throws \InvalidArgumentException Если тег не определён
     */
    protected static function getFileCachePathForTag(string $tag, ?string $lang = null): string
    {
        $defs = static::getFileCacheDefinitions();

        if (!isset($defs[$tag])) {
            throw new \InvalidArgumentException("Неизвестный тег кеша: $tag");
        }

        $lang = $lang ?: app()->getLocale();
        $name = $defs[$tag]['name'] ?? $tag;

        return static::getFileCacheDir() . "/{$name}_{$lang}.json";
    }

    /**
     * Рекурсивно локализует значение для указанного языка.
     *
     * Поддерживает:
     *  - строки вида {"ru": "...", "ua": "..."}
     *  - массивы языков ['ru' => '...', 'ua' => '...']
     *  - вложенные массивы
     *  - объекты произвольной структуры
     *
     * @param mixed $value Исходное значение
     * @param string $locale Язык локализации
     * @return mixed          Локализованное значение
     */
    protected static function localizeValue(mixed $value, string $locale): mixed
    {
        if (is_string($value) && static::looksLikeJsonLang($value)) {
            $arr = json_decode($value, true);
            return is_array($arr) ? ($arr[$locale] ?? reset($arr)) : $value;
        }

        // Массив: ['ru' => '...', 'ua' => ...]
        if (is_array($value) && static::isLangArray($value)) {
            return $value[$locale] ?? reset($value);
        }

        // Вложенный массив
        if (is_array($value)) {
            foreach ($value as $k => $v) {
                $value[$k] = static::localizeValue($v, $locale);
            }
            return $value;
        }

        // Объект
        if (is_object($value)) {
            foreach ($value as $prop => $v) {
                $value->$prop = static::localizeValue($v, $locale);
            }
            return $value;
        }

        return $value;
    }

    /**
     * Проверяет, является ли строка JSON-представлением языкового массива.
     *
     * Языки берутся динамически из languagesOfSite().
     *
     * @param string $val Проверяемая строка
     * @return bool        True, если строка похожа на JSON языков
     */
    protected static function looksLikeJsonLang(string $val): bool
    {
        $pattern = static::compileLangPattern();

        return (bool)preg_match('/^\{.*"(?:' . $pattern . ')".*\}$/u', $val);
    }

    /**
     * Проверяет, является ли массив языковым массивом.
     *
     * Массив считается языковым, если содержит хотя бы один ключ,
     * совпадающий с одним из языков сайта.
     *
     * @param array $arr Проверяемый массив
     * @return bool       True, если массив является языковым
     */
    protected static function isLangArray(array $arr): bool
    {
        $langs = static::availableLangs();

        return count(array_intersect(array_keys($arr), $langs)) > 0
            && !array_filter($arr, fn($v) => !is_scalar($v) && $v !== null);
    }

    /**
     * Получает кеш по тегу.
     *
     * Если файлы кеша отсутствуют — автоматически создаёт их.
     *
     * @param string $tag Имя набора кеша
     * @return \Illuminate\Support\Collection Коллекция данных
     */
    public static function getTaggedCache(string $tag): \Illuminate\Support\Collection
    {
        $path = static::getFileCachePathForTag($tag);

        if (!Storage::exists($path)) {
            static::updateFileCacheByTag($tag);
        }

        return collect(json_decode(Storage::get($path)) ?: []);
    }

    /**
     * Обновляет кеш по указанному тегу для всех языков.
     *
     * Выполняет локализацию данных перед сохранением.
     *
     * @param string $tag Имя набора кеша
     * @return void
     *
     * @throws \InvalidArgumentException Если тег не определён
     * @throws \LogicException Если колбэк выборки не задан
     */
    public static function updateFileCacheByTag(string $tag): void
    {
        $defs = static::getFileCacheDefinitions();
        if (!isset($defs[$tag])) {
            throw new \InvalidArgumentException("Неизвестный тег кеша: $tag");
        }

        $query = $defs[$tag]['query'] ?? null;
        if (!is_callable($query)) {
            throw new \LogicException("Неверный колбэк выборки для тега: $tag");
        }

        $originalLocale = app()->getLocale();
        $langs = static::availableLangs();

        $raw = collect($query());

        foreach ($langs as $lang) {
            app()->setLocale($lang);

            $localized = $raw->map(fn($item) => static::localizeValue($item, $lang));
            $path = static::getFileCachePathForTag($tag, $lang);

            $localized->isNotEmpty()
                ? Storage::put($path, $localized->toJson(JSON_UNESCAPED_UNICODE))
                : Storage::delete($path);
        }

        app()->setLocale($originalLocale);
    }

    /**
     * Обновляет все наборы кешей.
     *
     * @return void
     */
    public static function updateFileCache(): void
    {
        foreach (array_keys(static::getFileCacheDefinitions()) as $tag) {
            static::updateFileCacheByTag($tag);
        }
    }

    /**
     * Удаляет все файлы кеша для всех языков.
     *
     * @return void
     */
    public static function clearFileCache(): void
    {
        $langs = static::availableLangs();

        foreach (array_keys(static::getFileCacheDefinitions()) as $tag) {
            foreach ($langs as $lang) {
                Storage::delete(static::getFileCachePathForTag($tag, $lang));
            }
        }
    }

    /**
     * Регистрирует автоматическое обновление кеша
     * при создании, обновлении или удалении модели.
     *
     * @return void
     */
    protected static function bootHasFileCache(): void
    {
        $handler = fn() => static::updateFileCache();

        static::created($handler);
        static::updated($handler);
        static::deleted($handler);
    }

    /**
     * Возвращает массив доступных языков.
     *
     * @return array
     */
    protected static function availableLangs(): array
    {
        $langs = languagesOfSite();
        return $langs instanceof \Illuminate\Support\Collection ? $langs->all() : (array)$langs;
    }

    /**
     * Возвращает языки как строку для preg_match.
     *
     * @return string
     */
    protected static function compileLangPattern(): string
    {
        return implode('|', array_map('preg_quote', static::availableLangs()));
    }
}