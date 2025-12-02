<?php

namespace Vis\Builder;

use Illuminate\Routing\Router;
use Illuminate\Support\ServiceProvider;
use Illuminate\Support\Facades\View;
use Vis\Builder\Http\ViewComposers\ActivitiesTree;
use Vis\Builder\Http\ViewComposers\ChangeLang;
use Vis\Builder\Http\ViewComposers\Languages;
use Vis\Builder\Http\ViewComposers\LayoutDefault;
use Vis\Builder\Http\ViewComposers\Navigation;
use Vis\Builder\Http\ViewComposers\NavigationBadge;
use Vis\Builder\Models\TranslationsPhrases;
use Cartalyst\Sentinel\Laravel\Facades\Sentinel;

/**
 * Class BuilderServiceProvider.
 */
class BuilderServiceProvider extends ServiceProvider
{
    private $commandAdminInstall = 'command.admin.install';
    private $commandAdminGeneratePass = 'command.admin.generatePassword';
    private $commandAdminCreateConfig = 'command.admin.createConfig';
    private $commandAdminCreateImgWebp = 'command.admin.createImgWebp';

    /**
     * Bootstrap the application services.
     *
     * @return void
     */
    public function boot(\Illuminate\Routing\Router $router)
    {
        require __DIR__.'/../vendor/autoload.php';
        require __DIR__.'/Http/helpers.php';

        $this->app->setLocale(defaultLanguage());

        $router->middleware('auth.admin', \Vis\Builder\Authenticate::class);
        $router->middleware('auth.user', \Vis\Builder\AuthenticateFrontend::class);

        $this->setupRoutes($this->app->router);

        // Регистрация прероутера
        $this->registerPreRouter($router);

        $this->loadViewsFrom(realpath(__DIR__.'/resources/views'), 'admin');

        $this->publishes([
            __DIR__
            .'/published/assets' => public_path('packages/linecore/builder'),
            __DIR__.'/config'    => config_path('builder/'),
        ], 'builder');

        $this->publishes([
            __DIR__.'/config/cms.php' => config_path('builder/cms.php'),
        ], ['builder', 'builder-cms-config']);

        $this->publishes([
            __DIR__
            .'/published/assets' => public_path('packages/linecore/builder'),
        ], 'public');

        $this->publishes([
            realpath(__DIR__.'/Migrations') => $this->app->databasePath().'/migrations',
        ]);

        if ($this->app->runningInConsole()) {
            $this->commands([
                \Vis\Builder\Console\MakeDocCommand::class,
            ]);

            if (! is_dir(resource_path('docs'))) {
                if (!mkdir($concurrentDirectory = resource_path('docs'), 0755, true) && !is_dir($concurrentDirectory)) {
                    throw new \RuntimeException(sprintf('Directory "%s" was not created', $concurrentDirectory));
                }
            }

            $this->publishes([
                __DIR__.'/resources/docs/definitions' => resource_path('docs/definitions'),
            ], 'builder-docs');
        }

        $this->publishes([
            __DIR__.'/config/documentation.php' => config_path('builder/documentation.php'),
        ], 'builder-docs-config');

        $this->publishes([
            __DIR__.'/resources/views/documentation_page' =>
                resource_path('views/vendor/builder/documentation_page'),
        ], 'builder-docs-views');

        // Публикация конфига прероутера
        $this->publishes([
            __DIR__.'/config/prerouter.php' => config_path('builder/prerouter.php'),
        ], ['builder', 'prerouter-config']);

        $this->viewComposersInit();
    }

    /**
     * Регистрация прероутера (middleware и автоперегенерация кеша).
     */
    private function registerPreRouter(Router $router): void
    {
        if (! config('prerouter.enabled', false)) {
            return;
        }

        // Регистрация middleware
        $this->app[\Illuminate\Contracts\Http\Kernel::class]
            ->pushMiddleware(\Vis\Builder\Http\Middleware\CachePreRouter::class);

        // Слушатель событий для автоперегенерации кеша при cache:clear
        \Illuminate\Support\Facades\Event::listen(
            \Illuminate\Console\Events\CommandFinished::class,
            function (\Illuminate\Console\Events\CommandFinished $event) {
                // Автоперегенерація вимкнена
                if (!config('prerouter.auto_rebuild_on_cache_clear', true)) {
                    return;
                }

                $command = trim($event->command ?? '');

                // Порожня команда
                if ($command === '') {
                    return;
                }

                // Пропускаємо optimize (містить cache:clear)
                if (str_starts_with($command, 'optimize')) {
                    return;
                }

                // Ручне вимкнення: php artisan cache:clear --no-preroute
                if (str_contains($command, '--no-preroute')) {
                    return;
                }

                // Реагуємо лише на cache:clear
                if (str_starts_with($command, 'cache:clear')) {
                    try {
                        \Illuminate\Support\Facades\Artisan::call('prerouter:build');
                    } catch (\Throwable $e) {
                        logger()->error('Помилка prerouter:build', ['error' => $e->getMessage()]);
                    }
                }
            }
        );
    }

    private function viewComposersInit()
    {
        View::composer([
            'admin::partials.change_lang',
            'admin::partials.scripts'
        ],
            ChangeLang::class);

        View::composer('admin::partials.navigation_badge', NavigationBadge::class);
        View::composer('admin::partials.navigation', Navigation::class);

        View::composer(['admin::tree.partials.update',
            'admin::tree.partials.preview',
            'admin::tree.partials.clone',
            'admin::tree.partials.revisions',
            'admin::tree.partials.delete',
            'admin::tree.partials.constructor',
        ], ActivitiesTree::class);

        View::composer([
            'admin::translations.part.form_trans',
            'admin::translations.part.result_search',
            'admin::translations.part.table_center',
            'admin::translations.trans'
        ], Languages::class);




        View::composer('admin::layouts.default',  LayoutDefault::class);
    }

    /**
     * Define the routes for the application.
     *
     * @param \Illuminate\Routing\Router $router
     *
     * @return void
     */
    public function setupRoutes(Router $router)
    {
        // Якщо прероутер вимкнено (або не налаштовано), використовуємо стандартний роутинг
        if (! config('prerouter.enabled', false)) {
            require __DIR__.'/Http/route_frontend.php';
        }
        
        require __DIR__.'/Http/routers_translation_cms.php';
        require __DIR__.'/Http/routers.php';
        require __DIR__.'/Http/routers_translation.php';
    }

    /**
     * Register the application services.
     *
     * @return void
     */
    public function register()
    {
        // Register middleware if LocalizationMiddlewareRedirect class exists
        if (class_exists('Vis\Builder\LocalizationMiddlewareRedirect')) {
            $this->app[\Illuminate\Contracts\Http\Kernel::class]->pushMiddleware(LocalizationMiddlewareRedirect::class);
        }

        // Register middleware aliases
        if (method_exists(\Illuminate\Routing\Router::class, 'aliasMiddleware')) {
            $this->app[\Illuminate\Routing\Router::class]
                ->aliasMiddleware('auth.admin', \Vis\Builder\Authenticate::class);
            $this->app[\Illuminate\Routing\Router::class]
                ->aliasMiddleware('auth.user', \Vis\Builder\AuthenticateFrontend::class);
        }

        $this->app->bind(
            \Vis\Builder\Interfaces\DocSearchInterface::class,
            \Vis\Builder\Services\Documentation\FuzzyFileSearch::class
        );

        $this->registerCommands();

        $this->app->register(\Intervention\Image\Laravel\ServiceProvider::class);

        $loader = \Illuminate\Foundation\AliasLoader::getInstance();
        $loader->alias('Image', \Intervention\Image\Laravel\Facades\Image::class);
    }

    private function registerCommands()
    {
        $this->app->singleton($this->commandAdminInstall, function () {
            return new InstallCommand();
        });

        $this->app->singleton($this->commandAdminGeneratePass, function () {
            return new GeneratePassword();
        });

        $this->app->singleton($this->commandAdminCreateConfig, function () {
            return new CreateConfig();
        });

        $this->app->singleton($this->commandAdminCreateImgWebp, function () {
            return new CreateImgWebp();
        });

        $this->app->singleton('arrayTranslate', function () {
            return TranslationsPhrases::fillCacheTrans();
        });

        $this->commands($this->commandAdminInstall);
        $this->commands($this->commandAdminGeneratePass);
        $this->commands($this->commandAdminCreateConfig);
        $this->commands($this->commandAdminCreateImgWebp);
        
        // Регистрация команды прероутера
        if (config('prerouter.enabled', false)) {
            $this->commands([
                \Vis\Builder\Console\PreRouterBuild::class,
            ]);
        }
    }

    /**
     * @return array
     */
    public function provides()
    {
        return [
            $this->commandAdminInstall,
            $this->commandAdminGeneratePass,
            $this->commandAdminCreateConfig,
            $this->commandAdminCreateImgWebp,
        ];
    }
}
