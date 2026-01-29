<?php

/**
 * Linecore CMS - Content Management System for Laravel
 *
 * @package     Linecore\Cms
 * @author      Linecore Team <sales@linecore.com>
 * @copyright   2024 Linecore
 * @license     Proprietary
 */

namespace Linecore\Cms\Models;

use Illuminate\Database\Eloquent\Model;
use Linecore\Cms\Helpers\Traits\Rememberable;

class Language extends Model
{
    use Rememberable;

    protected $table = 'languages';
    protected $fillable = [];
    public $timestamps = false;
    private $supportedLocales;

    public function __construct()
    {
        $this->supportedLocales = config('laravellocalization.supportedLocales');
    }

    public static function scopeActive($query)
    {
        return $query->where('is_active', '1');
    }

    public static function scopeOrderPriority($query)
    {
        return $query->orderBy('priority', 'asc');
    }

    public function getName()
    {
        return $this->supportedLocales[$this->language]['name'] ?? '';
    }

    public function supportedLocales()
    {
        $result = [];

        foreach ($this->supportedLocales as $key => $info) {
            $result[$key] = $info['name'];
        }

        return $result;
    }

    public static function getDefaultLanguage()
    {
        return self::active()->orderPriority()
            ->rememberForever()->cacheTags(['languages'])
            ->first();
    }

    public function getLanguages()
    {
        return $this->active()->orderPriority()
            ->rememberForever()->cacheTags(['languages'])
            ->get();
    }
}
