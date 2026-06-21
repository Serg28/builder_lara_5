<?php

namespace Vis\Builder\Services;

use Closure;
use DateInterval;
use DateTimeInterface;
use Illuminate\Cache\Repository as CacheRepository;
use Illuminate\Contracts\Cache\Lock;
use Illuminate\Contracts\Cache\LockProvider;
use Illuminate\Contracts\Cache\LockTimeoutException;
use Illuminate\Contracts\Cache\Repository;
use Illuminate\Support\Facades\Cache;
use Throwable;

/**
 * Кешування із захистом від cache stampede.
 *
 * Проблема: звичайний Cache::remember() при промаху (після скидання кешу чи закінчення TTL)
 * пускає ВСІ паралельні запити в callback одночасно — десятки/сотні однакових важких запитів
 * у БД водночас перевантажують її. Цей клас гарантує, що значення будує лише ОДИН воркер.
 *
 * Реєстрація (один раз, у register() сервіс-провайдера — у BuilderServiceProvider):
 *
 *   public function register(): void
 *   {
 *       SingleFlightCache::registerMacros();
 *   }
 *
 * Після цього доступні два макроси на кеш-репозиторії (і через Cache::, і через Cache::tags()):
 *
 *   // flexibleLocked — stale-while-revalidate + single-flight:
 *   //   свіже → віддаємо одразу;
 *   //   протухле, але ще в кеші → віддаємо старе миттєво + оновлюємо у фоні (один воркер);
 *   //   холодний ключ → будує один воркер під блокуванням, решта чекають результат.
 *   Cache::flexibleLocked('key', [$freshSeconds, $totalSeconds], fn () => heavy());
 *   Cache::tags(['products'])->flexibleLocked('key', [3600, 3900], fn () => heavy());
 *
 *   // rememberLocked — блокуючий single-flight без stale:
 *   //   на промаху один воркер будує, решта чекають готовий результат.
 *   Cache::rememberLocked('key', 1200, fn () => heavy());
 *
 * Блокування неблокуюче для гарячого трафіку: на свіжому значенні lock не береться взагалі.
 *
 * flexibleLocked() повторює поведінку нативного Cache::flexible() (Laravel 11.23+) і сумісний
 * з ним за сигнатурою ($ttl = [fresh, total]) — це drop-in заміна. Додатково він бере блокування
 * і на холодному ключі (нативний flexible() цього не робить), тож захищає від stampede навіть
 * після повного скидання кешу. Ім'я навмисно не 'flexible' — щоб не конфліктувати з нативним
 * методом і не зламатись при апгрейді до Laravel 12+; за потреби міграція на нативний тривіальна.
 */
class SingleFlightCache
{
    public static function registerMacros(): void
    {
        if (! CacheRepository::hasMacro('flexibleLocked')) {
            CacheRepository::macro('flexibleLocked', function ($key, array $ttl, Closure $callback, int $lockSeconds = 10, int $waitSeconds = 7) {
                // $this — екземпляр репозиторію (зокрема Cache::tags([...])).
                return SingleFlightCache::flexible($this, $key, (int) $ttl[0], (int) $ttl[1], $callback, $lockSeconds, $waitSeconds);
            });
        }

        if (! CacheRepository::hasMacro('rememberLocked')) {
            CacheRepository::macro('rememberLocked', function ($key, $ttl, Closure $callback, int $lockSeconds = 10, int $waitSeconds = 7) {
                return SingleFlightCache::remember($this, $key, $ttl, $callback, $lockSeconds, $waitSeconds);
            });
        }
    }

    /**
     * Блокуючий single-flight: один будує, решта чекають готовий результат. Без stale.
     *
     * @param  DateTimeInterface|DateInterval|int  $ttl
     * @return mixed
     */
    public static function remember(
        Repository $repository,
        string $key,
        $ttl,
        Closure $callback,
        int $lockSeconds = 10,
        int $waitSeconds = 7
    ) {
        $value = $repository->get($key);
        if (! is_null($value)) {
            return $value;
        }

        $lock = self::makeLock($repository, $key, $lockSeconds);

        try {
            $lock->block($waitSeconds);

            // Поки чекали блокування, будівник міг уже наповнити кеш.
            $value = $repository->get($key);
            if (! is_null($value)) {
                return $value;
            }

            $value = $callback();
            $repository->put($key, $value, $ttl);

            return $value;
        } catch (LockTimeoutException) {
            // Не дочекались блокування — віддаємо що є, інакше рахуємо самі.
            $value = $repository->get($key);

            return ! is_null($value) ? $value : $callback();
        } finally {
            optional($lock)->release();
        }
    }

    /**
     * Гібрид stale-while-revalidate + single-flight.
     *
     * @param  int  $freshSeconds  Скільки значення вважається свіжим
     * @param  int  $totalSeconds  Коли ключ остаточно протухає (> $freshSeconds; різниця — вікно stale)
     * @return mixed
     */
    public static function flexible(
        Repository $repository,
        string $key,
        int $freshSeconds,
        int $totalSeconds,
        Closure $callback,
        int $lockSeconds = 10,
        int $waitSeconds = 7
    ) {
        $payload = $repository->get($key);

        if (self::isPayload($payload)) {
            if (time() < $payload['fresh_until']) {
                return $payload['value'];
            }

            // Протухле, але ще доступне: віддаємо stale одразу й оновлюємо у фоні.
            self::refreshInBackground($repository, $key, $freshSeconds, $totalSeconds, $callback, $lockSeconds);

            return $payload['value'];
        }

        return self::buildLocked($repository, $key, $freshSeconds, $totalSeconds, $callback, $lockSeconds, $waitSeconds);
    }

    private static function buildLocked(
        Repository $repository,
        string $key,
        int $freshSeconds,
        int $totalSeconds,
        Closure $callback,
        int $lockSeconds,
        int $waitSeconds
    ) {
        $lock = self::makeLock($repository, $key, $lockSeconds);

        try {
            $lock->block($waitSeconds);

            $payload = $repository->get($key);
            if (self::isPayload($payload)) {
                return $payload['value'];
            }

            return self::store($repository, $key, $freshSeconds, $totalSeconds, $callback());
        } catch (LockTimeoutException) {
            $payload = $repository->get($key);

            return self::isPayload($payload) ? $payload['value'] : $callback();
        } finally {
            optional($lock)->release();
        }
    }

    /**
     * Фонове оновлення: запускається ПІСЛЯ відповіді користувачу (terminating)
     * і лише одним воркером (неблокуючий захват lock).
     */
    private static function refreshInBackground(
        Repository $repository,
        string $key,
        int $freshSeconds,
        int $totalSeconds,
        Closure $callback,
        int $lockSeconds
    ): void {
        $lock = self::makeLock($repository, $key, $lockSeconds);

        if (! $lock->get()) {
            return; // інший воркер уже оновлює
        }

        $refresh = static function () use ($repository, $key, $freshSeconds, $totalSeconds, $callback, $lock) {
            try {
                self::store($repository, $key, $freshSeconds, $totalSeconds, $callback());
            } catch (Throwable $e) {
                report($e);
            } finally {
                optional($lock)->release();
            }
        };

        self::runAfterResponse($refresh);
    }

    /**
     * Запускає колбек «після відповіді», обираючи механізм під рантайм:
     *   - defer() (Laravel 11.23+) — Octane-aware, не блокує воркер;
     *   - app()->terminating() — під FPM спрацьовує після fastcgi_finish_request;
     *   - інакше (тести/CLI поза застосунком) — одразу.
     */
    private static function runAfterResponse(Closure $callback): void
    {
        if (function_exists('defer')) {
            // Виклик через call_user_func: defer() існує лише в Laravel 11.23+.
            call_user_func('defer', $callback);

            return;
        }

        $app = self::app();

        if ($app && method_exists($app, 'terminating')) {
            $app->terminating($callback);
        } else {
            $callback();
        }
    }

    /**
     * @param  mixed  $value
     * @return mixed
     */
    private static function store(Repository $repository, string $key, int $freshSeconds, int $totalSeconds, $value)
    {
        $repository->put($key, [
            'value' => $value,
            'fresh_until' => time() + $freshSeconds,
        ], $totalSeconds);

        return $value;
    }

    /**
     * @param  mixed  $payload
     */
    private static function isPayload($payload): bool
    {
        return is_array($payload)
            && array_key_exists('value', $payload)
            && isset($payload['fresh_until']);
    }

    /**
     * Блокування зі store репозиторію (RedisStore/ArrayStore — обидва LockProvider),
     * інакше — через дефолтний Cache::lock().
     */
    private static function makeLock(Repository $repository, string $key, int $lockSeconds): Lock
    {
        $lockKey = 'sf_lock:' . sha1($key);
        $store = $repository->getStore();

        if ($store instanceof LockProvider) {
            return $store->lock($lockKey, $lockSeconds);
        }

        return Cache::lock($lockKey, $lockSeconds);
    }

    private static function app()
    {
        try {
            return function_exists('app') ? app() : null;
        } catch (Throwable) {
            return null;
        }
    }
}
