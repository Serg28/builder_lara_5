<?php

namespace Vis\Builder\Fields;

use Closure;

/*
 * Приклад з форматуванням виводу:
 
ReadonlyField::make('Наявність на складах', 'availability_info')
    ->onlyForm()
    ->handleUsing(function ($value) {
        if(!empty($value->availability_info)) {
                return collect(json_decode($value->availability_info, true))
                    ->map(fn($v) => $v['title'].': <strong>'.$v['quantity'].'</strong>')
                    ->implode(', ');
        }
        return __cms('Немає інформації');
    }),
*/

class ReadonlyField extends Field
{
    protected ?Closure $callback = null;

    public function handleUsing(Closure $callback)
    {
        $this->callback = $callback;
        return $this;
    }

    public function setValue($value)
    {
        if (is_callable($this->callback)) {
            $this->value = call_user_func($this->callback, $value);
        } else {
            parent::setValue($value);
        }
    }

}
