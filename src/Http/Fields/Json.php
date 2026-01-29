<?php

namespace Linecore\Cms\Fields;

use Linecore\Cms\Fields\Text;

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
     * Количество «логических» колонок данных по умолчанию (1 = только значения, 2 = ключ/значение).
     * null означает автодетект по содержимому JSON (обратная совместимость).
     */
    protected ?int $defaultDataColumns = null;

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

    /**
     * Задать количество «логических» колонок по умолчанию: 1 (values) или 2 (key/value).
     * Если не вызывать — используется автодетект по JSON.
     */
    public function defaultDataColumns(int $columns = 2): self
    {
        $columns = in_array($columns, [1, 2], true) ? $columns : 2;
        $this->defaultDataColumns = $columns;
        return $this;
    }

    /**
     * Сокращение для defaultDataColumns(2).
     */
    public function twoColumnsByDefault(): self
    {
        return $this->defaultDataColumns(2);
    }

    /**
     * Сокращение для defaultDataColumns(1).
     */
    public function oneColumnByDefault(): self
    {
        return $this->defaultDataColumns(1);
    }

    public function getFieldForm($definition)
    {
        $field = $this;

        $valueArray = json_decode($field->value ?? '{}', true) ?? [];
        // Автодетект по умолчанию
        $isAssociative = self::isAssociativeArray($valueArray);
        // Переопределение по настройке defaultDataColumns только если значение пустое или требуется принудительный режим
        if ($this->defaultDataColumns !== null) {
            if ($this->defaultDataColumns === 1) {
                $isAssociative = false; // показывать одну колонку значений
            } elseif ($this->defaultDataColumns === 2) {
                $isAssociative = true; // показывать две колонки key/value
            }
        }

        return view('admin::form.fields.json', compact('definition', 'field', 'valueArray', 'isAssociative'))->render();
    }
}
