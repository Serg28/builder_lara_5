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

/**
 * Базовые настройки административной панели
 *
 * Абстрактный класс, определяющий базовую конфигурацию админ-панели:
 * заголовок, логотип, favicon и дополнительные CSS/JS ресурсы.
 *
 * @package Linecore\Cms\Setting
 */
abstract class AdminBase
{
    /**
     * Заголовок административной панели
     */
    protected string $caption = 'Linecore CMS';

    /**
     * URL логотипа в шапке
     */
    protected string $logoUrl = '/packages/linecore/cms/img/linecore-logo.png';

    /**
     * URL favicon
     */
    protected string $faviconUrl = '/packages/linecore/cms/img/favicon/favicon.ico';

    /**
     * Дополнительные CSS-файлы
     *
     * @var array<string>|null
     */
    protected ?array $css = null;

    /**
     * Дополнительные JS-файлы
     *
     * @var array<string>|null
     */
    protected ?array $js = null;

    /**
     * Получение списка разрешённых IP-адресов
     *
     * @return array<string>
     */
    public function accessIp(): array
    {
        $ipSetting = setting('ip');

        if (empty($ipSetting)) {
            return [];
        }

        return array_map('trim', explode(',', $ipSetting));
    }

    /**
     * Получение заголовка панели
     *
     * @return string
     */
    public function getCaption(): string
    {
        return __cms($this->caption);
    }

    /**
     * Получение URL логотипа
     *
     * @return string
     */
    public function getLogo(): string
    {
        return $this->logoUrl;
    }

    /**
     * Получение URL favicon
     *
     * @return string
     */
    public function getFaviconUrl(): string
    {
        return $this->faviconUrl;
    }

    /**
     * Получение списка дополнительных CSS-файлов
     *
     * @return array<string>|null
     */
    public function getCss(): ?array
    {
        return $this->css;
    }

    /**
     * Получение списка дополнительных JS-файлов
     *
     * @return array<string>|null
     */
    public function getJs(): ?array
    {
        return $this->js;
    }

    /**
     * Получение класса настроек страницы входа
     *
     * @return class-string<Login>
     */
    public function login(): string
    {
        return Login::class;
    }

    /**
     * Конфигурация дашборда
     *
     * Переопределите этот метод для настройки виджетов дашборда.
     *
     * @return void
     */
    public function dashboard(): void
    {
        // Переопределите в наследнике
    }

    /**
     * @deprecated Используйте dashboard()
     */
    public function dashbord(): void
    {
        $this->dashboard();
    }

    /**
     * Определение меню административной панели
     *
     * @return array<array>
     */
    abstract public function menu(): array;
}
