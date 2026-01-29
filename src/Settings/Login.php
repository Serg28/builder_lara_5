<?php

/**
 * Linecore CMS - Content Management System for Laravel
 *
 * @package     Linecore\Cms
 * @author      Linecore Team <sales@linecore.com>
 * @copyright   2024 Linecore
 * @license     Proprietary
 */

namespace Linecore\Cms\Setting;

use Illuminate\Support\Facades\Redirect;

class Login
{
    protected $backgroundUrl = '/packages/linecore/cms/img/login-bg.jpg';
    protected $css;

    public function onLogin()
    {
        return Redirect::to('/admin/tree');
    }

    public function onLogout()
    {
        return Redirect::to('/');
    }

    public function getBackground()
    {
        return $this->backgroundUrl;
    }

    public function getCss()
    {
        return $this->css;
    }
}
