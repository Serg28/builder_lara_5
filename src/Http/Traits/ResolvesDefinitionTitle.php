<?php

namespace Linecore\Cms\Helpers\Traits;

use Illuminate\Support\Str;
/**
 * Получение заголовка definition по имени.
 */
trait ResolvesDefinitionTitle
{
    protected function resolveDefinitionTitle(string $name): string
    {
        $class = '\\App\\Cms\\Definitions\\' . ucfirst(\Str::camel($name));

        return class_exists($class) ? $class::staticTitle() : ucfirst(Str::camel($name));
    }
}
