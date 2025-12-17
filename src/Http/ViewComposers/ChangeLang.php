<?php

namespace Vis\Builder\Http\ViewComposers;

use Illuminate\View\View;

class ChangeLang
{
    public function compose(View $view)
    {
        $languages = config("builder.translations.cms.languages");
        // $thisLang = request()->cookie('lang_admin') ?: config('builder.translations.cms.language_default');

        $thisLang = adminLang(false);

        $view->with(compact( 'languages', 'thisLang'));
    }
}
