<?php

namespace Vis\Builder\ControllersNew;

class ResetCacheController
{
    public function index() {
        opcache_reset();
        Artisan::call('cache:clear');
        Artisan::call('config:clear');
        Artisan::call('route:clear');
        Artisan::call('view:clear');
        Artisan::call('cache:clear_rebuild');
        return __cms('Кеш успішно очищено');
    }
}
