<?php

namespace Vis\Builder\Fields;

use Closure;
use Vis\Builder\Fields\Field;

/**
 * Поле для отображения вычисленного значения, которое может быть задано через коллбэк.
 *
 * Это поле не рендерит форму для ввода, а используется для отображения информации, например,
 * количества товаров в фиде, с использованием переданного коллбэка или любой другой кастомный код
 *
 * Пример использования:
 * ```php
 * CustomValue::make('Кол-во товаров')
 *     ->handleUsing(function ($feed) {
 *         // Логика вычисления количества товаров в фиде
 *         $feedClassName = '\\App\\Services\\Xml\\' . ucfirst($feed->feed_name);
 *         if (class_exists($feedClassName)) {
 *             $feedClass = app($feedClassName);
 *
 *             try {
 *                 return $feedClass->countProducts($feed);
 *             } catch (\Throwable $e) {
 *                 return 'н/д ' . $e->getMessage();
 *             }
 *         }
 *         return 0;
 *     }),
 *
 * CustomValue::make('Кол-во товаров')
 *      ->handleUsing(function ($feed) {
 *          // Логика вычисления количества товаров в фиде
 *          return $feedClass->countProducts($feed);
 *      }),
 * ```
 *
 * @method static CustomValue make(string $name) Создаёт новое поле с заданным именем.
 */
class CustomValue extends Field
{
    protected ?Closure $callback = null;

    /**
     * Устанавливает коллбэк, который будет вызываться для вычисления значения поля.
     *
     * @param Closure $callback Коллбэк, принимающий значение и объект модели (например, Feed).
     * @return static
     */
    public function handleUsing(Closure $callback): static
    {
        $this->callback = $callback;
        return $this;
    }

    /**
     * Устанавливает значение поля, вычисляя его через переданный коллбэк.
     *
     * @param mixed $value Значение, передаваемое в коллбэк для вычисления.
     * @return void
     */
    public function setValue($value): void
    {
        $this->value = is_callable($this->callback)
            ? call_user_func($this->callback, $value)
            : $value;
    }

    /**
     * Возвращает форму для поля, в данном случае всегда пустую, так как поле только для отображения.
     *
     * @param mixed $definition Определение поля.
     * @return string
     */
    public function getFieldForm($definition): string
    {
        return ''; // Ничего не рендерим, так как это только для отображения
    }
}
