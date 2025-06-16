<?php

namespace Vis\Builder\Http\Fields;

use Vis\Builder\Fields\Text;

/**
 * Поле для ввода массива данных в формате JSON в виде визуальной таблицы.
 * Позволяет добавлять, удалять и редактировать элементы массива,
 * а также изменять их порядок.
 *
 * Если json-массив без ключей в виде ["значение 1", "значение 2"] то таблица с одной колонкой
 * Если с ключами в виде [{"Название 1":"значение 2"}, {"Название 2":"значение 2"}] таблица с двумя колонками
  */

class Json extends Text
{
    /**
     * Проверяет, является ли массив ассоциативным.
     *
     * @param array $arr
     * @return bool
     */
    public static function isAssociativeArray(array $arr): bool
    {
        return !array_is_list($arr);
    }

    public function getFieldForm($definition)
    {
        $field = $this;

        $valueArray = json_decode($field->value ?? '{}', true) ?? [];
        $isAssociative = self::isAssociativeArray($valueArray);

        return view('admin::form.fields.json', compact('definition', 'field', 'valueArray', 'isAssociative'))->render();
    }
}
