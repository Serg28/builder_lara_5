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

use Illuminate\Http\RedirectResponse;
use Illuminate\Support\Facades\Redirect;

/**
 * Настройки страницы авторизации
 *
 * Определяет параметры отображения и поведение страницы входа
 * в административную панель CMS.
 *
 * @package Linecore\Cms\Setting
 */
class Login
{
    /**
     * URL фонового изображения страницы авторизации
     */
    protected string $backgroundUrl = '/packages/linecore/cms/img/login-background.jpg';

    /**
     * Дополнительные CSS-стили
     */
    protected ?string $css = null;

    /**
     * Действие после успешной авторизации
     *
     * @return RedirectResponse
     */
    public function onLogin(): RedirectResponse
    {
        return Redirect::to('/admin/tree');
    }

    /**
     * Действие после выхода из системы
     *
     * @return RedirectResponse
     */
    public function onLogout(): RedirectResponse
    {
        return Redirect::to('/');
    }

    /**
     * Получение URL фонового изображения
     *
     * @return string
     */
    public function getBackground(): string
    {
        return $this->backgroundUrl;
    }

    /**
     * Получение дополнительных CSS-стилей
     *
     * @return string|null
     */
    public function getCss(): ?string
    {
        return $this->css;
    }
}
