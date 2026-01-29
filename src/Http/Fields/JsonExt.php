<?php

namespace Linecore\Cms\Fields;

use Linecore\Cms\Fields\Text;

/**
 * Поле для редактирования массива объектов JSON вида:
 * [ {"key":"val", "key2":"val2"}, {"a":"b", "c":"d", "e":"f"} ]
 *
 * Поддерживает:
 * - Добавление/удаление/перемещение групп (объектов) вертикально
 * - Внутри каждой группы — добавление/удаление произвольного числа пар ключ/значение горизонтально
 */
class JsonExt extends Text
{
    /**
     * Совместимость с существующим API: задает bootstrap-колонки (1..12) как алиас к className('col-md-{n}').
     */
    public function columns(int $columns)
    {
        $columns = max(1, min(12, $columns));
        $this->className('col-md-' . $columns);

        return $this;
    }

    /**
     * Совместимость с существующим API. Для JsonExt не влияет на рендеринг,
     * добавлено только чтобы поддержать чейнинг и не падать с ошибкой.
     */
    public function defaultDataColumns(int $columns = 2): self
    {
        return $this;
    }

    /**
     * Совместимость: сокращение для defaultDataColumns(2).
     */
    public function twoColumnsByDefault(): self
    {
        return $this;
    }

    /**
     * Совместимость: сокращение для defaultDataColumns(1).
     */
    public function oneColumnByDefault(): self
    {
        return $this;
    }

    public function getFieldForm($definition)
    {
        $field = $this;

        $raw = $field->value ?? '[]';
        $valueArray = json_decode($raw, true);
        if (!is_array($valueArray)) {
            $valueArray = [];
        }

        // Нормализуем к массиву ассоциативных массивов
        $normalized = [];
        foreach ($valueArray as $group) {
            $normalized[] = is_array($group) ? $group : [];
        }

        return view('admin::form.fields.json_ext', [
            'definition' => $definition,
            'field' => $field,
            'groups' => $normalized,
        ])->render();
    }
}


