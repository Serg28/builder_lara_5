<?php

/**
 * Linecore CMS - Content Management System for Laravel
 *
 * @package     Linecore\Cms
 * @author      Linecore Team <sales@linecore.com>
 * @copyright   2024 Linecore
 * @license     Proprietary
 */

namespace Linecore\Cms;

use Illuminate\Contracts\Http\Kernel;
use Illuminate\Routing\Router;
use Illuminate\Support\Facades\View;
use Illuminate\Support\ServiceProvider;
use Linecore\Cms\Console\MakeDocCommand;
use Linecore\Cms\Http\ViewComposers\ActivitiesTree;
use Linecore\Cms\Http\ViewComposers\ChangeLang;
use Linecore\Cms\Http\ViewComposers\Languages;
use Linecore\Cms\Http\ViewComposers\LayoutDefault;
use Linecore\Cms\Http\ViewComposers\Navigation;
use Linecore\Cms\Http\ViewComposers\NavigationBadge;
use Linecore\Cms\Models\TranslationsPhrases;
use RuntimeException;

/**
 * Linecore CMS Service Provider
 *
 * Главный сервис-провайдер CMS. Выполняет регистрацию всех компонентов системы,
 * настройку маршрутов, middleware и публикацию ресурсов.
 *
 * @package Linecore\Cms
 */
class CmsServiceProvider extends ServiceProvider
{
    /**
     * Идентификаторы консольных команд
     */
    private const CMD_INSTALL = 'linecore.cms.install';
    private const CMD_GENERATE_PASSWORD = 'linecore.cms.password';
    private const CMD_CREATE_CONFIG = 'linecore.cms.config';
    private const CMD_CREATE_WEBP = 'linecore.cms.webp';

    /**
     * Пространство имен для представлений административной панели
     */
    protected string $viewNamespace = 'admin';

    /**
     * Инициализация сервисов приложения
     *
     * Выполняет загрузку конфигурации, регистрацию middleware,
     * настройку маршрутов и публикацию ресурсов пакета.
     *
     * @param Router $router Экземпляр маршрутизатора
     * @return void
     */
    public function boot(Router $router): void
    {
        $this->loadDependencies();
        $this->configureLocale();
        $this->registerMiddleware($router);
        $this->setupRoutes($router);
        $this->registerViews();
        $this->registerPublishableResources();
        $this->registerConsoleCommands();
        $this->initializeViewComposers();
    }

    /**
     * Загрузка зависимостей пакета
     *
     * @return void
     */
    protected function loadDependencies(): void
    {
        require __DIR__ . '/../vendor/autoload.php';
        require __DIR__ . '/Http/helpers.php';
    }

    /**
     * Настройка локализации приложения
     *
     * @return void
     */
    protected function configureLocale(): void
    {
        $this->app->setLocale(defaultLanguage());
    }

    /**
     * Регистрация middleware для авторизации
     *
     * @param Router $router Экземпляр маршрутизатора
     * @return void
     */
    protected function registerMiddleware(Router $router): void
    {
        $router->middleware('auth.admin', Authenticate::class);
        $router->middleware('auth.user', AuthenticateFrontend::class);
    }

    /**
     * Регистрация представлений пакета
     *
     * @return void
     */
    protected function registerViews(): void
    {
        $viewsPath = realpath(__DIR__ . '/resources/views');
        $this->loadViewsFrom($viewsPath, $this->viewNamespace);
    }

    /**
     * Регистрация публикуемых ресурсов
     *
     * @return void
     */
    protected function registerPublishableResources(): void
    {
        $assetsPath = __DIR__ . '/published/assets';
        $configPath = __DIR__ . '/config';

        // Основные ресурсы CMS
        $this->publishes([
            $assetsPath => public_path('packages/linecore/cms'),
            $configPath => config_path('cms/'),
        ], 'linecore-cms');

        // Публичные ассеты
        $this->publishes([
            $assetsPath => public_path('packages/linecore/cms'),
        ], 'public');

        // Миграции базы данных
        $this->publishes([
            realpath(__DIR__ . '/Migrations') => $this->app->databasePath() . '/migrations',
        ]);

        // Конфигурация документации
        $this->publishes([
            __DIR__ . '/config/documentation.php' => config_path('cms/documentation.php'),
        ], 'cms-docs-config');

        // Представления документации
        $this->publishes([
            __DIR__ . '/resources/views/documentation_page' => resource_path('views/vendor/cms/documentation_page'),
        ], 'cms-docs-views');
    }

    /**
     * Регистрация консольных команд
     *
     * @return void
     */
    protected function registerConsoleCommands(): void
    {
        if (!$this->app->runningInConsole()) {
            return;
        }

        $this->commands([MakeDocCommand::class]);
        $this->ensureDocsDirectoryExists();

        $this->publishes([
            __DIR__ . '/resources/docs/definitions' => resource_path('docs/definitions'),
        ], 'cms-docs');
    }

    /**
     * Создание директории для документации
     *
     * @return void
     * @throws RuntimeException
     */
    protected function ensureDocsDirectoryExists(): void
    {
        $docsPath = resource_path('docs');

        if (is_dir($docsPath)) {
            return;
        }

        if (!mkdir($docsPath, 0755, true) && !is_dir($docsPath)) {
            throw new RuntimeException(
                sprintf('Не удалось создать директорию документации: %s', $docsPath)
            );
        }
    }

    /**
     * Инициализация View Composers
     *
     * Регистрирует композеры для автоматической передачи данных в представления.
     *
     * @return void
     */
    protected function initializeViewComposers(): void
    {
        // Переключатель языков
        View::composer([
            'admin::partials.change_lang',
            'admin::partials.scripts'
        ], ChangeLang::class);

        // Навигация
        View::composer('admin::partials.navigation_badge', NavigationBadge::class);
        View::composer('admin::partials.navigation', Navigation::class);

        // Действия для древовидной структуры
        View::composer([
            'admin::tree.partials.update',
            'admin::tree.partials.preview',
            'admin::tree.partials.clone',
            'admin::tree.partials.revisions',
            'admin::tree.partials.delete',
            'admin::tree.partials.constructor',
        ], ActivitiesTree::class);

        // Переводы
        View::composer([
            'admin::translations.part.form_trans',
            'admin::translations.part.result_search',
            'admin::translations.part.table_center',
            'admin::translations.trans'
        ], Languages::class);

        // Основной layout
        View::composer('admin::layouts.default', LayoutDefault::class);
    }

    /**
     * Настройка маршрутов приложения
     *
     * @param Router $router Экземпляр маршрутизатора
     * @return void
     */
    public function setupRoutes(Router $router): void
    {
        $routesPath = __DIR__ . '/Http/';

        require $routesPath . 'route_frontend.php';
        require $routesPath . 'routers_translation_cms.php';
        require $routesPath . 'routers.php';
        require $routesPath . 'routers_translation.php';
    }

    /**
     * Регистрация сервисов приложения
     *
     * Выполняет привязку интерфейсов к реализациям и регистрацию
     * синглтонов для ключевых компонентов системы.
     *
     * @return void
     */
    public function register(): void
    {
        $this->registerLocalizationMiddleware();
        $this->registerMiddlewareAliases();
        $this->registerBindings();
        $this->registerSingletons();
    }

    /**
     * Регистрация middleware локализации
     *
     * @return void
     */
    protected function registerLocalizationMiddleware(): void
    {
        $this->app[Kernel::class]->pushMiddleware(LocalizationMiddlewareRedirect::class);
    }

    /**
     * Регистрация алиасов middleware
     *
     * @return void
     */
    protected function registerMiddlewareAliases(): void
    {
        if (!method_exists(Router::class, 'aliasMiddleware')) {
            return;
        }

        $router = $this->app[Router::class];
        $router->aliasMiddleware('auth.admin', Authenticate::class);
        $router->aliasMiddleware('auth.user', AuthenticateFrontend::class);
    }

    /**
     * Регистрация привязок интерфейсов
     *
     * @return void
     */
    protected function registerBindings(): void
    {
        $this->app->bind(
            Interfaces\DocSearchInterface::class,
            Services\Documentation\FuzzyFileSearch::class
        );
    }

    /**
     * Регистрация синглтонов
     *
     * @return void
     */
    protected function registerSingletons(): void
    {
        // Сервис переводов
        $this->app->singleton(Services\Translate::class, function () {
            return new Services\Translate();
        });

        // Кэш переводов
        $this->app->singleton('arrayTranslate', function () {
            return TranslationsPhrases::fillCacheTrans();
        });

        // Консольные команды
        $this->app->singleton(self::CMD_INSTALL, fn() => new InstallCommand());
        $this->app->singleton(self::CMD_GENERATE_PASSWORD, fn() => new GeneratePassword());
        $this->app->singleton(self::CMD_CREATE_CONFIG, fn() => new CreateConfig());
        $this->app->singleton(self::CMD_CREATE_WEBP, fn() => new CreateImgWebp());

        $this->commands([
            self::CMD_INSTALL,
            self::CMD_GENERATE_PASSWORD,
            self::CMD_CREATE_CONFIG,
            self::CMD_CREATE_WEBP,
        ]);
    }

    /**
     * Получение списка предоставляемых сервисов
     *
     * @return array<string>
     */
    public function provides(): array
    {
        return [
            self::CMD_INSTALL,
            self::CMD_GENERATE_PASSWORD,
            self::CMD_CREATE_CONFIG,
            self::CMD_CREATE_WEBP,
        ];
    }
}
