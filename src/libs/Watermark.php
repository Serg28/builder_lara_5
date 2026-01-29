<?php

namespace Linecore\Cms;

use Illuminate\Support\Facades\Config;
use Intervention\Image\Filters\FilterInterface;
use Intervention\Image\Image;

class Watermark implements FilterInterface
{
    public function applyFilter(Image $image)
    {
        return $image->widen(config('cms.watermark.width'))->insert(
            config('cms.watermark.path_watermark'),
            config('cms.watermark.position'),
            config('cms.watermark.x'),
            config('cms.watermark.y')
        );
    }
}
