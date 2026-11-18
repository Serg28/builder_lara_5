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

        // ponytail: только схлопываем "//storage" -> "/storage"; "storage/x" (без слеша), "https://" и "//host.tld/" не трогаем
        $fix = fn ($p) => is_string($p) && !str_contains($p, '://') && !preg_match('#^//[^/]*\.[^/]*/#', $p)
            ? preg_replace('#^/{2,}#', '/', $p) : $p;

        $fixed = array_values(array_unique(array_map($fix, $paths)));

        return $fixed === $paths ? $value : json_encode($fixed, JSON_UNESCAPED_SLASHES | JSON_UNESCAPED_UNICODE);
    }
}
