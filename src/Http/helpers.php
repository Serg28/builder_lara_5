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
                return Cache::tags('language')->rememberLocked('default_language', null, function () {
                    return optional(Language::getDefaultLanguage())->language ?: config('app.locale');
                });
            } catch (\Exception $e) {
                return config('app.locale');
            }
        });
    }
}

/*
if (! function_exists('languagesOfSite')) {
    function languagesOfSite()
    {
        return (new Language())->getLanguages()->pluck('language');
    }
}*/
if (! function_exists('languagesOfSite')) {
    function languagesOfSite()
    {
        try {
            return (new Language())->getLanguages()->pluck('language');
        } catch (QueryException $e) {
            // Возвращаем пустую коллекцию, если таблицы нет
            return collect();
        }
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
if (! function_exists('setting')) {
    function setting(string $slug)
    {
        static $loadedConfigs = [];

        $key = 'setting_'.$slug . App::getLocale();

        if (array_key_exists($key, $loadedConfigs)) {
            $value = $loadedConfigs[$key];
        } else {
            try {
                $value = Cache::tags(['settings'])->rememberLocked($key, 3600, function () use ($slug) {
                    return (new Settings())->model()->getValue($slug);
                });
                $loadedConfigs[$key] = $value;
            } catch (QueryException $e) {
                return null;
            } catch (Exception $e) {
                return null;
            }
        }

        return $value;
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
        // при переаплоаде с тем же именем старая нарезка сама перестаёт совпадать по ключу.
        $fileKey = null;
        if (is_string($source) && file_exists(public_path($source))) {
            $filePath = public_path($source);
            $fileKey = filemtime($filePath) . '_' . filesize($filePath);
        }

        $cacheKey = 'glide_' . md5($source . json_encode($options) . '_' . $fileKey);
        $tag = cache()->tags(['glide']);

        // Лок здесь не берём — иначе лишний Redis-раунд-трип на каждый кэш-хит под ботами.
        $cached = $tag->get($cacheKey);
        if (is_string($cached) && file_exists(public_path($cached))) {
            return $cached;
        }
        // false — закешированная ранее неудача генерации, не пробуем заново на каждый запрос.
        if ($cached === false) {
            return '/packages/vis/builder/img/no_image.png';
        }

        if (
            env('IMG_PLACEHOLDER', true)
            && (env('APP_ENV') === 'local' || env('APP_ENV') === 'testing')
        ) {
            $width = $options['w'] ?? 100;
            $height = $options['h'] ?? 100;
            return "//placehold.co/{$width}x{$height}";
        }

        // Лок только на этом (редком) пути — иначе шторм одинаковых запросов нарежет файл параллельно много раз.
        $lock = Cache::lock('glide_lock:' . $cacheKey, 10);

        try {
            $lock->block(7);

            // Пока ждали лок, другой воркер мог уже успеть нарезать файл или закешировать неудачу.
            $cached = $tag->get($cacheKey);
            if (is_string($cached) && file_exists(public_path($cached))) {
                return $cached;
            }
            if ($cached === false) {
                return '/packages/vis/builder/img/no_image.png';
            }

            $path = (new Vis\Builder\Img())->get($source, $options);

            // Кешируем только реально записанный файл — иначе путь зависал бы в кеше до ручного flush тега glide.
            if ($path && file_exists(public_path($path)) && filesize(public_path($path)) > 0) {
                // TTL, не forever — пакет используют разные проекты, не у всех есть job, флашащий тег glide.
                $tag->put($cacheKey, $path, now()->addDays(30));

                return $path;
            }

            // Короткий TTL вместо forever — чтобы шторм ботов по битому пути не долбил Image::make() на каждый запрос, но само восстановилось.
            $tag->put($cacheKey, false, now()->addMinutes(5));

            return '/packages/vis/builder/img/no_image.png';
        } catch (\Illuminate\Contracts\Cache\LockTimeoutException) {
            // Не дождались лока — не плодим ещё одну генерацию, отдаём что есть.
            $cached = $tag->get($cacheKey);

            if (is_string($cached) && file_exists(public_path($cached))) {
                return $cached;
            }

            return '/packages/vis/builder/img/no_image.png';
        } finally {
            optional($lock)->release();
        }
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
            $thisLang = adminLang(false);

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
            $thisLang = adminLang(false);

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
