<?php

namespace Linecore\Cms\Http\ViewComposers;

use Illuminate\View\View;
use Linecore\Cms\Models\Language;

class Languages
{
    public function compose(View $view)
    {
        $languages = (new Language())->getLanguages();

        $view->with(compact( 'languages'));
    }
}
