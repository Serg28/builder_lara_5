<?php

namespace Linecore\Cms\Services;

use Linecore\Cms\Interfaces\Button;

class ButtonStrategy
{
   private $button;

   public function __construct(Button $button)
   {
       $this->button = $button;
   }

   public function render()
   {
       return $this->button->show();
   }
}