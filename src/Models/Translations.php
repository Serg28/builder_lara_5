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

class Translations extends Model
{
    protected $table = 'translations';
    public $timestamps = false;
    protected $fillable = ['id_translations_phrase', 'lang', 'translate'];
}
