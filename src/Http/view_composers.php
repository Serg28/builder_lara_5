<?php

/**
 * Linecore CMS - View Composers
 *
 * @package     Linecore\Cms
 * @author      Linecore Team <sales@linecore.com>
 * @copyright   2024 Linecore
 * @license     Proprietary
 */

use Illuminate\View\View as ViewParam;

/*
|--------------------------------------------------------------------------
| Navigation Composer
|--------------------------------------------------------------------------
*/
View::composer('admin::partials.navigation', function (ViewParam $view) {
    $user = Sentinel::getUser();
    $menu = config('cms.admin.menu');

    $view->with('user', $user)->with('menu', $menu);
});

/*
|--------------------------------------------------------------------------
| Default Layout Composer
|--------------------------------------------------------------------------
*/
View::composer(['admin::layouts.default', 'admin::partials.scripts'], function (ViewParam $view) {
    $skin = Cookie::get('skin') ?: 'smart-style-4';
    $thisLang = Cookie::get('lang_admin') ?: config('cms.translations.cms.language_default');
    $customJs = config('cms.admin.custom_js');
    $customCss = config('cms.admin.custom_css');
    
    $logo = config('cms.admin.logo_url') ?: '/packages/linecore/cms/img/linecore-logo.png';
    $logoWhite = config('cms.admin.logo_url_white') ?: '/packages/linecore/cms/img/linecore-logo-white.png';

    if ($skin && $skin !== 'smart-style-0') {
        $logo = $logoWhite;
    }

    $view->with(compact('skin', 'thisLang', 'customJs', 'customCss', 'logo'));
});

/*
|--------------------------------------------------------------------------
| New Layout Composer
|--------------------------------------------------------------------------
*/
View::composer(['admin::new.layouts.default', 'admin::new.partials.scripts'], function (ViewParam $view) {
    $admin = new \App\Cms\Admin();

    $skin = Cookie::get('skin') ?: 'smart-style-4';
    $thisLang = Cookie::get('lang_admin') ?: config('cms.translations.cms.language_default');
    $customJs = config('cms.admin.custom_js');
    $customCss = config('cms.admin.custom_css');
    $logoWhite = config('cms.admin.logo_url_white') ?: '/packages/linecore/cms/img/linecore-logo-white.png';

    $logo = ($skin && $skin !== 'smart-style-0') ? $logoWhite : $logoWhite;

    $view->with(compact('skin', 'thisLang', 'customJs', 'customCss', 'logo', 'admin'));
});

View::composer('admin::new.partials.navigation', function (ViewParam $view) {
    $user = Sentinel::getUser();
    $menu =  (new \App\Cms\Admin())->menu();


    $view->with(compact('user', 'menu'));
});


View::composer(['admin::tree.create_modal', 'admin::tree.content'], function (ViewParam $view) {
    $templates = config('cms.'.$view->treeName.'.templates');
    $model = config('cms.'.$view->treeName.'.model');
    $idNode = request('node', 1);

    if ($idNode && $model) {
        $info = $model::find($idNode);
        if (isset($info->template)) {
            $accessTemplateShow =
                config('cms.'.$view->treeName.'.templates.'.$info->template.'.show_templates');

            if (is_array($accessTemplateShow) && count($accessTemplateShow)) {
                $accessTemplateShow = array_flip($accessTemplateShow);

                $templates = array_intersect_key($templates, $accessTemplateShow);
            }
        }
    }

    $view->with('templates', $templates);
});

View::composer(['admin::tree.partials.update',
                     'admin::tree.partials.preview',
                     'admin::tree.partials.clone',
                     'admin::tree.partials.revisions',
                     'admin::tree.partials.delete',
                     'admin::tree.partials.constructor',
], function (ViewParam $view) {
    $type = $view->getData()['type'];
    $active = true;
    $caption = '';

    $view->with(compact('active', 'caption'));
});
