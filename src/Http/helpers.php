<?php

use Illuminate\Database\QueryException;
use Vis\Builder\Models\TranslationsCms;
use Vis\Builder\Models\TranslationsPhrasesCms;
use Vis\Builder\Models\Language;
use Illuminate\Support\Facades\Cache;
use App\Cms\Definitions\Settings;
use Illuminate\Support\Facades\App;
use Vis\Builder\Services\Translate;

if (! function_exists('defaultLanguage')) {
    function defaultLanguage(): ?string
    {
        return once(function () {
            try {
                return Cache::tags('language')->rememberForever('default_language', function () {
                    return optional(Language::getDefaultLanguage())->language ?: config('app.locale');
                });
            } catch (\Exception $e) {
                return config('app.locale');
            }
        });
    }
}

if (! function_exists('languagesOfSite')) {
    function languagesOfSite()
    {
        return rescue(
            fn () => (new Language())->getLanguages()
                ->pluck('language')
                ->whenEmpty(fn () => collect(['ua','ru','en'])),

            collect(['ua','ru','en'])
        );
    }
}

if (! function_exists('adminLang')) {
    function adminLang(bool $normalizeUaKey = true) : string
    {
        $lang = Cookie::get('lang_admin') ?: config('builder.translations.cms.language_default');

        if($normalizeUaKey) {
            // совместимость со старым кодом
            return $lang === 'uk' ? 'ua' : $lang;
        }

        return $lang;
    }
}
/*
if (! function_exists('setting')) {

    function setting(string $slug)
    {
        return Cache::tags('settings')->rememberForever($slug . App::getLocale(), function() use ($slug) {
            return (new Settings())->model()->getValue($slug);
        });
    }
}*/
if (! function_exists('setting')) {
    function setting(string $slug)
    {
        try {
            //return Cache::tags('settings')->rememberForever($slug . App::getLocale(), function() use ($slug) {
            return Cache::tags('settings')->remember('setting_'.$slug . App::getLocale(), 1200, function() use ($slug) {
                return (new Settings())->model()->getValue($slug);
            });
        } catch (QueryException $e) {
            // Возвращаем null, если возникла ошибка работы с таблицей
            return null;
        } catch (Exception $e) {
            // Перехватываем любые другие ошибки (например, связанные с кешем)
            return null;
        }
    }
}

if (! function_exists('filesize_format')) {

    function filesize_format($bytes)
    {
        if ($bytes >= 1073741824) {
            $bytes = number_format($bytes / 1073741824, 1, '.', '').' Gb';
        } elseif ($bytes >= 1048576) {
            $bytes = number_format($bytes / 1048576, 1, '.', '').' Mb';
        } elseif ($bytes >= 1024) {
            $bytes = number_format($bytes / 1024, 1, '.', '').' Kb';
        } elseif ($bytes > 1) {
            $bytes = $bytes.' bytes';
        } elseif ($bytes == 1) {
            $bytes = $bytes.' byte';
        } else {
            $bytes = '0 bytes';
        }

        return $bytes;
    }
}

if (! function_exists('settingForMail')) {

    function settingForMail(string $value)
    {
        return array_map('trim', explode(',', setting($value)));
    }
}

if (! function_exists('dr')) {
    function dr($array)
    {
        echo '<pre>';
        die(print_r($array));
    }
}

if (! function_exists('print_arr')) {
    function print_arr($array)
    {
        echo '<pre>';
        print_r($array);
        echo '</pre>';
    }
}

if (!function_exists('glide')) {
    function glide($source, array $options = [])
    {
        // Уникальный ключ кеша на основе пути и параметров
        $cacheKey = 'glide_' . md5($source . json_encode($options));

        // Проверяем, есть ли данные в кеше
        $cachedPath = cache()->tags(['glide'])->get($cacheKey);

        // Если путь закеширован и файл действительно существует, сразу возвращаем
        if ($cachedPath && file_exists(public_path($cachedPath))) {
            return $cachedPath;
        }

        // Проверяем, есть ли данные в кеше
        return cache()->tags(['glide'])->rememberForever($cacheKey, function () use ($source, $options) {
            if (
                config('builder.image.img_placeholder', true)
                && (config('app.env') === 'local' || config('app.env') === 'testing')
            ) {
                $width = $options['w'] ?? 100;
                $height = $options['h'] ?? 100;
                return "//placehold.co/{$width}x{$height}";
            }

            // Если плейсхолдер не используется, вызываем метод get()
            return app(Vis\Builder\Img::class)->get($source, $options);
        });
    }
}

if (! function_exists('geturl')) {
    function geturl( string $url, $locale = false, array $attributes = []) : string
    {
        if (! $locale) {
            $locale = App::getLocale();
        }

        return LaravelLocalization::getLocalizedURL($locale, $url, $attributes);
    }
}

if (! function_exists('__cms')) {
    function __cms($phrase, array $replacePhrase = []) : ?string
    {
        return once(function () use ($phrase, $replacePhrase) {
            // $thisLang = Cookie::get('lang_admin', config('builder.translations.cms.language_default'));
            // $thisLang = adminLang(false);
            // $thisLang = config('builder.translations.cms.language_default', 'uk');
            $thisLang = Cookie::get('lang_admin') ?: config('builder.translations.cms.language_default', 'uk');

            $arrayTranslate = TranslationsPhrasesCms::fillCacheTrans();

            if (!isset($arrayTranslate[$phrase][$thisLang])) {
                if ($phrase) {
                    (new TranslationsCms())->createNewTranslate($phrase);
                }
            }

            $result = $arrayTranslate[$phrase][$thisLang] ?? $phrase;

            if (!empty($replacePhrase)) {
                $result = str_replace(array_keys($replacePhrase), array_values($replacePhrase), $result);
            }

            return $result;
        }, [$phrase, $replacePhrase]);
    }
}

if (! function_exists('__t')) {
    function __t(string $phrase, array $replacePhrase = []) : ?string
    {
        return once(function () use ($phrase, $replacePhrase) {
            //return (new Translate())->returnPhrase($phrase, $replacePhrase);
            return app(Translate::class)->returnPhrase($phrase, $replacePhrase);
        }, [$phrase, $replacePhrase]);
    }
}

// ============================================================================
// URL Helpers (перенесено з app/Helpers/Helpers.php для автономності пакету)
// ============================================================================

if (! function_exists('currentUrl')) {
    /**
     * Отримує поточний URL, з можливістю виключити GET-параметри та/або домен.
     *
     * Якщо поточний URL містить 'livewire/update', функція поверне значення
     * заголовка `referer`. В іншому випадку повернеться поточний URL.
     *
     * @param bool $withoutQuery  Вказує, чи потрібно повернути URL без GET-параметрів.
     * @param bool $withoutDomain Вказує, чи потрібно повернути URL без домену (тільки шлях). За замовчуванням — false.
     */
    function currentUrl(bool $withoutQuery = false, bool $withoutDomain = false): ?string
    {
        $currentUrl = request()->fullUrl();
        $refererUrl = request()->header('referer') ?? '';

        // Якщо URL містить `livewire/update`, використовуємо referer
        $url = str_contains($currentUrl, 'livewire/update') ? $refererUrl : $currentUrl;

        // Фільтруємо пусті GET-параметри для запобігання проблем з Livewire-компонентами
        $url = removeEmptyQueryParams($url);

        // Прибираємо GET-параметри, якщо потрібно
        if ($withoutQuery) {
            $url = str_contains($url, '?') ? explode('?', $url)[0] : $url;
        }

        // Прибираємо домен, якщо потрібно
        if ($withoutDomain) {
            $parsed = parse_url($url);
            $path = $parsed['path'] ?? '/';
            $query = isset($parsed['query']) && ! $withoutQuery ? '?' . $parsed['query'] : '';
            $url = $path . $query;
        }

        return $url;
    }
}

if (! function_exists('currentUrlPath')) {
    /**
     * Повертає поточний URL - тільки частину path (без домену та get-параметрів),
     * навіть якщо виклик відбувся з livewire-компонента.
     */
    function currentUrlPath(): ?string
    {
        $url = currentUrl();

        // Функція для витягування шляху з URL
        $getPath = static function ($url): string {
            $parsedUrl = parse_url($url);
            return isset($parsedUrl['path']) ? ltrim($parsedUrl['path'], '/') : '/';
        };

        return $url ? $getPath($url) : '/';
    }
}

if (! function_exists('urlPathWithoutLocale')) {
    /**
     * Отримати шлях без домену та мовного префіксу (без початкового слешу).
     * Якщо $pathOrUrl не вказано, використовується поточний URL.
     *
     * @param string|null $pathOrUrl Шлях або повний URL.
     */
    function urlPathWithoutLocale(?string $pathOrUrl = null): ?string
    {
        static $cached = null;

        if ($pathOrUrl === null && $cached !== null) {
            return $cached;
        }

        // Отримуємо шлях без ведучого слешу
        $path = $pathOrUrl
            ? ltrim(parse_url($pathOrUrl, PHP_URL_PATH) ?? '', '/')
            : currentUrlPath();

        if ($path === '') {
            return null;
        }

        $segments = explode('/', $path);

        $defaultLocale = defaultLanguage();
        $supportedLocales = languagesOfSite()->toArray();

        $firstSegment = $segments[0] ?? null;

        if ($firstSegment !== $defaultLocale && in_array($firstSegment, $supportedLocales, true)) {
            array_shift($segments);
        }

        $result = implode('/', $segments);

        if ($pathOrUrl === null) {
            $cached = $result;
        }

        return $result;
    }
}

if (! function_exists('getQueryParam')) {
    /**
     * Отримує значення вказаного GET-параметра з поточного URL.
     *
     * Працює коректно і при звичайних HTTP-запитах, і при AJAX-запитах Livewire.
     *
     * @param  string      $param Ім'я GET-параметра, значення якого потрібно отримати.
     * @param  mixed|null  $default Значення за замовчуванням, якщо параметр відсутній.
     * @return string|null Повертає значення параметра або null, якщо параметр відсутній.
     */
    function getQueryParam(string $param, mixed $default = null): ?string
    {
        // Пріоритет: $_GET (актуальний URL в браузері)
        if (isset($_GET[$param])) {
            return $_GET[$param];
        }

        // Якщо немає в $_GET — пробуємо з Laravel-запиту
        return request()->query($param, $default);
    }
}

if (! function_exists('isLivewireQuery')) {
    /*
     * Перевірка, чи запит був від Livewire
     */
    function isLivewireQuery(): bool
    {
        return (bool) request()->header('x-livewire') !== null;
    }
}

if (! function_exists('removeEmptyQueryParams')) {
    /**
     * Удаляет пустые GET-параметры из URL.
     *
     * Функция фильтрует параметры со значениями пустой строки ('') или null,
     * сохраняя параметры со значением '0' или false, так как они могут быть валидными.
     *
     * @param  string $url URL для обработки
     * @return string URL без пустых GET-параметров
     */
    function removeEmptyQueryParams(string $url): string
    {
        // Ранний возврат, если URL не содержит query-строку
        if (! str_contains($url, '?')) {
            return $url;
        }

        // Разбираем URL
        $parsed = parse_url($url);

        // Если нет query-строки после парсинга, возвращаем URL как есть
        if (! isset($parsed['query']) || $parsed['query'] === '') {
            return $url;
        }

        // Парсим query-параметры
        parse_str($parsed['query'], $params);

        // Фильтруем пустые значения (сохраняем '0' и false как валидные)
        $filteredParams = array_filter($params, static fn ($value) => $value !== '' && $value !== null);

        // Собираем URL обратно
        $scheme = isset($parsed['scheme']) ? $parsed['scheme'].'://' : '';
        $host = $parsed['host'] ?? '';
        $port = isset($parsed['port']) ? ':'.$parsed['port'] : '';
        $path = $parsed['path'] ?? '';
        $query = ! empty($filteredParams) ? '?'.http_build_query($filteredParams) : '';
        $fragment = isset($parsed['fragment']) ? '#'.$parsed['fragment'] : '';

        return $scheme.$host.$port.$path.$query.$fragment;
    }
}
