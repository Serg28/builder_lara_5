<?php

namespace Linecore\Cms\Services;

use Illuminate\Contracts\View\View;
use Linecore\Cms\Interfaces\Button;

class ButtonExample extends ButtonBase implements Button
{
    public function show():View
    {
        return view('admin::list.buttons.button_example');
    }
}