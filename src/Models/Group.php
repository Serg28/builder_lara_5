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

class Group extends Model
{
    protected $table = 'roles';
    protected $fillable = ['name', 'slug', 'permissions'];

    public $timestamps = false;
}
