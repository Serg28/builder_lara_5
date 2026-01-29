<?php

/**
 * Linecore CMS - Content Management System for Laravel
 *
 * @package     Linecore\Cms
 * @author      Linecore Team <sales@linecore.com>
 * @copyright   2024 Linecore
 * @license     Proprietary
 */

namespace Linecore\Cms;

use App\Models\Group;
use Cartalyst\Sentinel\Activations\EloquentActivation;
use Cartalyst\Sentinel\Users\EloquentUser;

/**
 * Модель пользователя CMS
 *
 * Расширяет EloquentUser из Sentinel для работы с пользователями
 * административной панели. Предоставляет методы для работы с аватарами,
 * группами и проверки прав доступа.
 *
 * @package Linecore\Cms
 *
 * @property int $id
 * @property string $email
 * @property string $first_name
 * @property string $last_name
 * @property string|null $picture
 */
class User extends EloquentUser
{
    /**
     * Таблица модели
     */
    protected $table = 'users';

    /**
     * Путь к аватару по умолчанию
     */
    protected const DEFAULT_AVATAR = '/packages/linecore/cms/img/default-avatar.gif';

    /**
     * Связь с группами пользователя
     *
     * @return \Illuminate\Database\Eloquent\Relations\BelongsToMany
     */
    public function groups()
    {
        return $this->belongsToMany(
            Group::class,
            'role_users',
            'user_id',
            'role_id'
        );
    }

    /**
     * Связь с активацией аккаунта
     *
     * @return \Illuminate\Database\Eloquent\Relations\HasOne
     */
    public function activation()
    {
        return $this->hasOne(EloquentActivation::class);
    }

    /**
     * Установка массива заполняемых полей
     *
     * @param array<string> $params Список полей
     * @return void
     */
    public function setFillable(array $params): void
    {
        $this->fillable = $params;
    }

    /**
     * Получение URL аватара пользователя
     *
     * @param array<string, mixed> $imgParam Параметры изображения для glide
     * @return string URL аватара
     */
    public function getAvatar(array $imgParam): string
    {
        $imagePath = $this->picture ?? self::DEFAULT_AVATAR;

        return glide($imagePath, $imgParam);
    }

    /**
     * Получение полного имени пользователя
     *
     * @return string Полное имя (Имя Фамилия)
     */
    public function getFullName(): string
    {
        return trim("{$this->first_name} {$this->last_name}");
    }

    /**
     * Проверка доступа к разделу CMS
     *
     * @param string $link Путь к разделу
     * @param string $action Тип действия (view, edit, delete и т.д.)
     * @return bool
     */
    public function hasAccessForCms(string $link, string $action = 'view'): bool
    {
        $cleanLink = explode('?', $link)[0];
        $permission = str_replace('/', '', $cleanLink) . '.' . $action;

        return $this->hasAccess([$permission]);
    }

    /**
     * Проверка доступа к действиям в текущем разделе CMS
     *
     * @param string $action Тип действия
     * @return bool
     */
    public function hasAccessActionsForCms(string $action): bool
    {
        $urlSegments = explode('/', request()->path());
        $currentSection = last($urlSegments);

        // Разрешаем доступ для групп и foreign_field запросов
        if (request()->is('*/groups') || request()->has('foreign_field')) {
            return true;
        }

        return $this->hasAccessForCms($currentSection, $action);
    }
}
