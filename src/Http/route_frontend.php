<?php

$rawPath = Request::path();
$rawFirstSegment = explode('/', $rawPath)[0];

if ($rawFirstSegment != 'admin' && !str_contains($rawPath, 'livewire') && $rawFirstSegment != 'api') {
    try {
        $urlPath = Request::path();
        $arrSegments = explode('/', $urlPath);

        $controllerMethodArray = app(\Vis\Builder\Interfaces\TreeResolverInterface::class)->getRoute($arrSegments);

        if ($controllerMethodArray) {
            Route::group(
                ['middleware' => ['web']],
                static function () use ($controllerMethodArray) {
                    Route::group(
                        ['prefix' => LaravelLocalization::setLocale()],
                        static function () use ($controllerMethodArray) {
                            Route::get(
                                $controllerMethodArray['node']->getUrlNoLocation(),
                                static function () use ($controllerMethodArray) {
                                    return $controllerMethodArray['controller']
                                        ->callAction('init', [$controllerMethodArray['node'], $controllerMethodArray['method']]);
                                }
                            );
                        }
                    );
                }
            );
        }
    } catch (Exception $e) {
    }
}