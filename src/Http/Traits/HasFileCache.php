<?php

namespace Vis\Builder\Http\Traits;

use Illuminate\Support\Collection;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\Storage;

/**
 * Трейт для оптимізованого файлового кешування моделей з підтримкою L2 (через фасад Cache).
 *
 *  Кешує вибірки, минаючи стандартний кеш Laravel
 *
 *  Дозволяє визначати кілька наборів даних, що кешуються (тегів),
 *  кожен з яких зберігається у власний файл і може мати
 *  індивідуальні правила вибірки даних.
 *
 *  Підтримує автоматичну локалізацію значень при генерації кешу,
 *  а також оновлення кешів при створенні, зміні та видаленні моделей.
 *
 * ЧОМУ ЦЕ ВИКОРИСТОВУЄТЬСЯ:
 * 1. Швидкість: Читання JSON файлу або отримання з Redis (L2) в десятки разів швидше за запит до БД.
 * 2. Економія ресурсів: Відсутня "hydration" (створення важких об'єктів Eloquent) при кожному хіті.
 * 3. Незалежність: Вирішує проблему некоректних URL в контексті Livewire (/livewire/update),
 *    оскільки дані (включаючи URL) зберігаються в кеш в "чистому" стані основного запиту.
 *
 *  ---
 *  Як використовувати:
 *
 *  1. Підключіть трейт у вашу модель:
 *       use HasFileCache;
 *
 *  2. Перевизначте метод getFileCacheDefinitions(), де задаються:
 *       - тег (наприклад: 'default', 'short', 'grouped')
 *       - ім'я файлу кешу (опціонально)
 *       - колбек для вибірки даних
 *
 *  3. Отримання кешу:
 *       static::getTaggedCache('short');
 *
 *  4. Оновлення всіх кешів:
 *       static::updateFileCache();
 *
 *  ---
 *  Кожен кеш створюється окремо для кожної мови,
 *  що повертається функцією languagesOfSite().
 *
 * ПРИКЛАД ВИЗНАЧЕННЯ В МОДЕЛІ:
 * ```php
 * protected static function getFileCacheDefinitions(): array
 * {
 *     return [
 *         'main_menu' => [
 *             'name' => 'main_menu',
 *             'query' => fn () => static::query()->where('is_active', 1)->get(['id', 'title']),
 *             'return_array' => true,
 *             'use_l2_cache' => true
 *         ]
 *     ];
 * }
 * ```
 *
 */
trait HasFileCache
{
    /** @var array Кеш імен таблиць для моделей */
    protected static array $tableNameCache = [];

    /**
     * Отримати директорію для зберігання файлів кешу.
     * За замовчуванням: storage/app/model_cache/
     */
    protected static function getFileCacheDir(): string
    {
        return 'model_cache';
    }

    /**
     * Визначення кешу для моделі.
     * Перевизначається в моделі для задання кастомних вибірок.
     *
     * Ключі конфігурації:
     * - name: (string) базова назва файлу кешу.
     * - query: (callable) функція, що повертає QueryBuilder, Collection або масив.
     * - return_array: (bool) якщо true, дані в кеші будуть масивами, а не об'єктами/моделями.
     * - use_l2_cache: (bool) якщо true, дані будуть дублюватися в Redis.
     */
    protected static function getFileCacheDefinitions(): array
    {
        return [
            'default' => [
                'name' => static::getFileCacheName(),
                'query' => fn () => static::query()->get(),
                'return_array' => false,
                'use_l2_cache' => false,
            ],
        ];
    }

    /**
     * Базова назва файлу кешу на основі таблиці моделі.
     */
    protected static function getFileCacheName(?string $lang = null): string
    {
        $lang = $lang ?: app()->getLocale();
        $class = static::class;

        return self::$tableNameCache[$class]
            ??= with(new static)->getTable() . '_' . $lang;
    }

    /**
     * Отримати шлях до файлу кешу для конкретного тегу.
     *
     * @throws \InvalidArgumentException
     */
    protected static function getFileCachePathForTag(string $tag, ?string $lang = null): string
    {
        $defs = static::getFileCacheDefinitions();

        if (! isset($defs[$tag])) {
            throw new \InvalidArgumentException("Невідомий тег кешу: $tag");
        }

        $lang = $lang ?: app()->getLocale();
        $name = $defs[$tag]['name'] ?? $tag;

        return static::getFileCacheDir() . "/{$name}_{$lang}.json";
    }

    /**
     * Рекурсивна локалізація значень.
     * Автоматично визначає багатомовні поля (JSON або масиви)
     * і замінює їх на значення для поточної локалі.
     */
    protected static function localizeValue(mixed $value, string $locale): mixed
    {
        if (is_string($value) && static::looksLikeJsonLang($value)) {
            $arr = json_decode($value, true);

            return is_array($arr) ? ($arr[$locale] ?? reset($arr)) : $value;
        }

        if (is_array($value) && static::isLangArray($value)) {
            return $value[$locale] ?? reset($value);
        }

        if (is_array($value)) {
            foreach ($value as $k => $v) {
                $value[$k] = static::localizeValue($v, $locale);
            }

            return $value;
        }

        if (is_object($value)) {
            foreach ($value as $prop => $v) {
                $value->$prop = static::localizeValue($v, $locale);
            }

            return $value;
        }

        return $value;
    }

    /**
     * Перевірка, чи є рядок JSON-ом з перекладами.
     * Використовує regex за списком доступних мов сайту.
     */
    protected static function looksLikeJsonLang(string $val): bool
    {
        $pattern = static::compileLangPattern();

        return (bool) preg_match('/^\{.*"(?:' . $pattern . ')".*\}$/u', $val);
    }

    /**
     * Перевірка, чи є масив словником перекладів.
     */
    protected static function isLangArray(array $arr): bool
    {
        $langs = static::availableLangs();

        return count(array_intersect(array_keys($arr), $langs)) > 0
            && ! array_filter($arr, static fn ($v) => ! is_scalar($v) && $v !== null);
    }

    /**
     * Отримати дані з кешу за тегом.
     * 1. Шукає в Redis (якщо увімкнено use_l2_cache).
     * 2. Якщо немає в Redis, шукає в локальному файлі.
     * 3. Якщо файлу немає, генерує кеш заново.
     */
    public static function getTaggedCache(string $tag): Collection
    {
        $defs = static::getFileCacheDefinitions();
        $useL2 = $defs[$tag]['use_l2_cache'] ?? false;

        if ($useL2) {
            $lang = app()->getLocale();
            $cacheKey = 'file_cache:' . static::class . ":{$tag}:{$lang}";

            return Cache::rememberForever($cacheKey, static function () use ($tag) {
                return static::loadFromFilesystem($tag);
            });
        }

        return static::loadFromFilesystem($tag);
    }

    /**
     * Завантажити дані безпосередньо з файлової системи.
     */
    protected static function loadFromFilesystem(string $tag): Collection
    {
        $path = static::getFileCachePathForTag($tag);

        if (! Storage::exists($path)) {
            static::updateFileCacheByTag($tag);
        }

        $defs = static::getFileCacheDefinitions();
        $asArray = $defs[$tag]['return_array'] ?? false;

        return collect(json_decode(Storage::get($path), $asArray) ?: []);
    }

    /**
     * Оновити файли кешу (та L2 Redis) для конкретної конфігурації (тегу).
     * Генерує файли ОДРАЗУ для всіх мов, доступних на сайті.
     *
     * @throws \InvalidArgumentException|\LogicException
     */
    public static function updateFileCacheByTag(string $tag): void
    {
        $defs = static::getFileCacheDefinitions();
        if (! isset($defs[$tag])) {
            throw new \InvalidArgumentException("Невідомий тег кешу: $tag");
        }

        $query = $defs[$tag]['query'] ?? null;
        if (! is_callable($query)) {
            throw new \LogicException("Невірний колбек вибірки для тегу: $tag");
        }

        $originalLocale = app()->getLocale();
        $langs = static::availableLangs();
        $useL2 = $defs[$tag]['use_l2_cache'] ?? false;

        foreach ($langs as $lang) {
            app()->setLocale($lang);

            $raw = $query();

            // Виконання Query Builder якщо передано його, а не колекцію
            if ($raw instanceof \Illuminate\Database\Eloquent\Builder || $raw instanceof \Illuminate\Database\Query\Builder) {
                $raw = $raw->get();
            }

            // Автоматичне приведення до масиву (включаючи вкладені колекції/моделі)
            $asArray = $defs[$tag]['return_array'] ?? false;
            if ($asArray && is_object($raw) && method_exists($raw, 'toArray')) {
                $raw = $raw->toArray();
            }

            // Рекурсивна локалізація даних перед збереженням
            $localized = static::localizeValue($raw, $lang);

            // Приведення до колекції для Json-серіалізації
            $localizedData = collect(is_array($localized) && ! empty($localized) && ! array_is_list($localized) ? [$localized] : $localized);

            $path = static::getFileCachePathForTag($tag, $lang);

            if ($localizedData->isNotEmpty()) {
                Storage::put($path, $localizedData->toJson(JSON_UNESCAPED_UNICODE));
            } else {
                Storage::delete($path);
            }

            // Примусове оновлення L2 кешу, якщо увімкнено
            if ($useL2) {
                $cacheKey = 'file_cache:' . static::class . ":{$tag}:{$lang}";
                Cache::forever($cacheKey, $localizedData);
            }
        }

        app()->setLocale($originalLocale);
    }

    /**
     * Оновити всі визначені кеші для цієї моделі.
     */
    public static function updateFileCache(): void
    {
        foreach (array_keys(static::getFileCacheDefinitions()) as $tag) {
            static::updateFileCacheByTag($tag);
        }
    }

    /**
     * Обробка вузла даних перед записом у кеш.
     * Можна перевизначити в моделі для кастомного очищення або модифікації даних.
     */
    protected static function processFileCacheNode(mixed $item, string $lang): mixed
    {
        // Рекурсивна обробка 'children' для деревоподібних структур
        if (is_array($item) && isset($item['children']) && is_array($item['children'])) {
            foreach ($item['children'] as $key => $child) {
                $item['children'][$key] = static::processFileCacheNode($child, $lang);
            }
        } elseif (is_object($item) && isset($item->children)) {
            foreach ($item->children as $child) {
                static::processFileCacheNode($child, $lang);
            }
        }

        return $item;
    }

    /**
     * Очищення файлів кешу та записів у Redis для всіх мов і тегів моделі.
     */
    public static function clearFileCache(): void
    {
        $langs = static::availableLangs();
        foreach (array_keys(static::getFileCacheDefinitions()) as $tag) {
            foreach ($langs as $lang) {
                Storage::delete(static::getFileCachePathForTag($tag, $lang));

                $cacheKey = 'file_cache:' . static::class . ":{$tag}:{$lang}";
                Cache::forget($cacheKey);
            }
        }
    }

    /**
     * Автоматичний зв'язок подій Eloquent з інвалідацією кешу.
     * При створенні, оновленні або видаленні запису - кеш перестворюється.
     */
    protected static function bootHasFileCache(): void
    {
        $handler = static fn () => static::updateFileCache();
        static::created($handler);
        static::updated($handler);
        static::deleted($handler);
    }

    /**
     * Список мов сайту для процесів генерації кешу.
     */
    protected static function availableLangs(): array
    {
        $langs = languagesOfSite();

        return $langs instanceof Collection ? $langs->all() : (array) $langs;
    }

    /**
     * Регулярний вираз для виявлення ключів локалізації в JSON.
     */
    protected static function compileLangPattern(): string
    {
        return implode('|', array_map('preg_quote', static::availableLangs()));
    }
}
