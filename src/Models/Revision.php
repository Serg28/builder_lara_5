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
use \App\Models\User;

class Revision extends Model
{
    protected $table = 'revisions';

    protected $fillable = ['revisionable_type', 'revisionable_id', 'user_id', 'key', 'old_value', 'new_value'];

    public function user()
    {
        return $this->belongsTo(User::class);
    }
}
