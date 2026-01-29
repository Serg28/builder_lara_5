<?php

namespace Linecore\Cms\Helpers\Traits;

use Linecore\Cms\ViewPage;

/**
 * Trait ViewPageTrait.
 */
trait ViewPageTrait
{
    public function setView()
    {
        ViewPage::create([
           'model'     => get_class($this),
           'id_record' => $this->id,
        ]);
    }
}
