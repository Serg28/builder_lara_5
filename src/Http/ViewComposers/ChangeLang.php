<?php

namespace Linecore\Cms\Http\ViewComposers;

use Illuminate\View\View;

class ChangeLang
{
    public function compose(View $view)
    {
        $languages = config("cms.translations.cms.languages");
        // $thisLang = request()->cookie('lang_admin') ?: config('cms.translations.cms.language_default');

        $thisLang = adminLang(false);

        $view->with(compact( 'languages', 'thisLang'));
    }
}
