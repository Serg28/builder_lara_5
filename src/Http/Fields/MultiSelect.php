<?php

namespace Vis\Builder\Fields;

use Illuminate\Support\Arr;

class MultiSelect extends Select
{
    public $onlyForm = true;

    public function getValueArray()
    {
        return json_decode($this->getValue());
    }

    public function getValue()
    {
        return json_decode(parent::getValue()) ?? [];
    }

    public function getValueForList($definition)
    {
        $options = $this->getOptions();

        return collect(Arr::wrap($this->getValue()))
            ->filter(fn($v) => $v !== null && $v !== '')   // убираем пустые значения
            ->map(fn($v) => $options[$v] ?? $v)           // заменяем ключи на метки
            ->implode(', ');                              // объединяем в строку
    }

    public function prepareSave($request)
    {
        $nameField = $this->getNameField();

        return json_encode($request[$nameField] ?? []);
    }
}
