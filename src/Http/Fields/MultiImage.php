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

        $fix = fn ($p) => is_string($p) && $p !== '' && !str_contains($p, '://') && !preg_match('#^//[^/]*\.[^/]*/#', $p)
            ? '/'.ltrim($p, '/') : $p; // ponytail: "//host.tld/x" и "https://" не трогаем

        return json_encode(array_values(array_unique(array_map($fix, $paths))), JSON_UNESCAPED_SLASHES | JSON_UNESCAPED_UNICODE);
    }
}
