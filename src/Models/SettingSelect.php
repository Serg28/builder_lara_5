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

use Illuminate\Database\Eloquent\Model as Eloquent;

class SettingSelect extends Eloquent
{
    protected $table = 'setting_select';

    //Некоторые правила валидиции
    public static $rules = [
        'value'      => 'required',
        'id_setting' => 'required|numeric',
    ];

    public $timestamps = false;

    public static function doDelete($id)
    {
        if (is_numeric($id)) {
            self::find($id)->delete();
            Setting::reCacheSettings();
        }
    }

    //end doDelete
}
