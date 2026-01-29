<?php

namespace Linecore\Cms\Fields;

class Id extends Field
{
    public function getValueForList($definition)
    {
        return '<a onclick="TableBuilder.getEditForm('.$this->value.', $(this));">'.$this->value.'</a>';
    }
}
