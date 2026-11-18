<?php

namespace Vis\Builder\Fields;

class MultiImage extends Image
{
    public $onlyForm = true;

    public function getValueForList($definition)
    {
        return '';
    }

    public function getValueArray()
    {
        return json_decode($this->getValue());
    }

    // защита: не пишем в БД пути с ведущим "//"
    public function prepareSave(array $request)
    {
        $value = parent::prepareSave($request);
        $paths = is_string($value) ? json_decode($value, true) : null;

        if (!is_array($paths)) {
            return $value;
        }

        return json_encode(array_map(fn ($p) => is_string($p) && $p !== '' ? '/'.ltrim($p, '/') : $p, $paths), JSON_UNESCAPED_SLASHES | JSON_UNESCAPED_UNICODE);
    }
}
