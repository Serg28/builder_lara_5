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

use Illuminate\Database\Eloquent\Model;
use Linecore\Cms\Helpers\Traits\TranslateTrait;

class Setting extends Model
{
    use TranslateTrait;

    protected $table = 'settings';
    protected $fillable = [];
    public $timestamps = false;

    public function getValue(string $slug)
    {
        $setting = $this->whereSlug($slug)->first();

        if ($setting) {
            return $this->getResultType($setting)[$setting->type] ?? '';
        }
    }

    protected function getResultType($setting)
    {
        return [
            'text' => $setting->value,
            'text_with_languages' => $setting->t('value_languages'),
            'textarea_with_languages' => $setting->t('textarea_with_languages'),
            'froala_with_languages' => $setting->t('froala_with_languages'),
            'file' => $setting->file,
            'checkbox' => $setting->check
        ];
    }
}
