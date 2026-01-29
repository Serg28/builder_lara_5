<?php

/**
 * Linecore CMS - Content Management System for Laravel
 *
 * @package     Linecore\Cms
 * @author      Linecore Team <sales@linecore.com>
 * @copyright   2024 Linecore
 * @license     Proprietary
 */

namespace Linecore\Cms\Fields;

use Illuminate\Support\Str;
use Linecore\Cms\Models\Language;

/**
 * Базовый класс поля формы
 *
 * Определяет базовую функциональность для всех типов полей в административной панели.
 * Наследуйте этот класс для создания кастомных типов полей.
 *
 * @package Linecore\Cms\Fields
 */
abstract class Field
{
    /** @var string Отображаемое название поля */
    protected string $name;

    /** @var string Атрибут модели */
    protected string $attribute;

    /** @var bool Отображать только в форме редактирования */
    protected bool $onlyForm = false;

    /** @var bool Быстрое редактирование в таблице */
    protected bool $fastEdit = false;

    /** @var mixed Текущее значение поля */
    public $value = '';

    /** @var mixed Значения для мультиязычных полей */
    protected $valueLanguage;

    /** @var bool Возможность сортировки по полю */
    protected bool $isSortable = false;

    /** @var mixed Значение по умолчанию */
    protected $defaultValue;

    /** @var string|null Placeholder для поля ввода */
    protected ?string $placeholderValue = null;

    /** @var array|null Правила валидации */
    protected ?array $rules = null;

    /** @var string|null Текст для пустого значения в выпадающем списке */
    protected ?string $nullValue = null;

    /** @var bool Мультиязычное поле */
    protected $language;

    /** @var bool Поле типа ManyToMany */
    protected bool $isManyToMany = false;

    /** @var mixed Настройки фильтра */
    protected $filter;

    /** @var string Текст комментария-подсказки */
    protected string $commentText = '';

    /** @var string|null Связь HasOne */
    protected ?string $relationHasOne = null;

    /** @var string|null Связь MorphOne */
    protected ?string $relationMorphOne = null;

    /** @var string|null CSS-класс для поля */
    protected ?string $classNameField = null;

    /** @var mixed Все данные записи */
    protected $allData;

    /** @var string Текущая локаль */
    protected string $locale;

    /** @var bool Только для чтения при редактировании */
    protected bool $isReadonlyForEdit = false;

    /** @var bool Автоматический перевод */
    protected bool $isAutoTranslate = false;

    /** @var bool Скрытое поле */
    protected bool $isHide = false;

    /** @var bool Сохранение при изменении */
    protected bool $isSaveOnChange = false;

    /**
     * Создание нового экземпляра поля
     *
     * @param string $name Отображаемое название
     * @param string|null $attribute Атрибут модели (по умолчанию генерируется из названия)
     */
    public function __construct(string $name, ?string $attribute = null)
    {
        $this->name = $name;
        $this->attribute = $attribute ?? str_replace(' ', '_', Str::lower($name));
        $this->locale = config('app.locale');
    }

    /**
     * Нормализация JSON-строки
     *
     * Исправляет проблемы с переносами строк и табуляцией в JSON.
     *
     * @param string $value Исходная JSON-строка
     * @return string Нормализованная JSON-строка
     */
    protected function normalizeJson(string $value): string
    {
        $value = preg_replace("/[\r\n]+/", "\\r\\n", $value);
        $value = str_replace("\t", '\t', $value);

        return json_encode(
            json_decode($value),
            JSON_UNESCAPED_SLASHES | JSON_UNESCAPED_UNICODE
        );
    }

    /**
     * @deprecated Используйте normalizeJson()
     */
    protected function fixJson($value)
    {
        return $this->normalizeJson($value);
    }

    public function setValue($value)
    {
        $this->allData = $value;

        if ($this->getHasOne()) {
            $relation = $value->{$this->getHasOne()};

            if ($this->getLanguage()) {
                if ($relation) {
                    //$this->valueLanguage = json_decode($relation->{$this->attribute});
                    $this->valueLanguage = json_decode($this->fixJson($relation->{$this->attribute}));
                    $this->value = $relation ? $relation->{$this->attribute} : '';
                }

                return;
            }

            $this->value = $relation ? $relation->{$this->attribute} : '';

            return;
        }

        if ($this->getMorphOne()) {
            $relation = $value->{$this->getMorphOne()};

            if ($this->getLanguage()) {
                if ($relation) {
                    //$this->valueLanguage = json_decode($relation->{$this->attribute});
                    $this->valueLanguage = json_decode($this->fixJson($relation->{$this->attribute}));
                }

                return;
            }

            $this->value = $relation ? $relation->{$this->getNameField()} : $relation;

            return;
        }

        if ($this->getLanguage() && isset($value[$this->attribute]) && $value[$this->attribute]) {
            //$this->valueLanguage = json_decode($value[$this->attribute]);
            $this->valueLanguage = json_decode($this->fixJson($value[$this->attribute]));
        }

        $this->value = $value[$this->attribute] ?? '';
    }

    public function getAllData()
    {
        return $this->allData;
    }

    public function className($class)
    {
        if (is_null($this->classNameField)) {
            $this->classNameField = $class;
        } else {
            $this->classNameField .= " $class";
        }

        return $this;
    }

    public function getClassName()
    {
        return $this->classNameField ? 'section_field '. $this->classNameField : '';
    }

    public function getId()
    {
        return isset($this->allData->id) ? $this->allData->id : '';
    }

    public function getValue()
    {
        return $this->value ? $this->value : $this->defaultValue;
    }

    public function checkAutoTranslate()
    {
        return $this->isAutoTranslate;
    }

    public function isOnlyForm()
    {
        return $this->onlyForm;
    }

    public function isFilter()
    {
        return $this->filter;
    }

    public function getValueLanguage($postfix)
    {
        return $this->valueLanguage->$postfix ?? '';
    }

    public function getName()
    {
        return __cms($this->name);
    }

    public function getNameField()
    {
        if ($this->getHasOne()) {
            return $this->attribute . '_' . $this->getHasOne();
        }

        return $this->attribute;
    }

    public function getNameFieldLangTab($definition, $tab)
    {
        return $definition->getNameDefinition() . $this->getNameField() . $tab->language;
    }

    public function getNameFieldWithDefinition($definition)
    {
        return $definition->getNameDefinition() . '_'.  $this->getNameField();
    }

    public function getNameFieldInBd()
    {
        return $this->attribute;
    }

    public function getValueForList($definition)
    {
        //$arrayValue = json_decode($this->getValue());
        $arrayValue = json_decode($this->getValue() ?? '{}');

        $value = $arrayValue->{$this->locale} ?? $this->getValue();

        if ($this->fastEdit) {

            $idRecord = $this->getId();
            $field = $this->getNameFieldInBd();

            return view('admin::list.fast_edit.field_base', compact('idRecord', 'value', 'field'));
        }

        return $value;
    }

    public function getValueForExel($definition)
    {
        $arrayValue = json_decode($this->getValue());

        return $arrayValue->{$this->locale} ?? $this->getValue();
    }

    public function isOrder($list)
    {
        $order = session($list->getDefinition()->getSessionKeyOrder());

        return $order && $order['field'] == $this->getNameField() ? 'sorting_'.$order['direction'] : '';
    }

    public function getFilter($list)
    {
        $filter = session($list->getDefinition()->getSessionKeyFilter());

        return $filter && isset($filter['filter'][$this->getNameField()]) ?
            $filter['filter'][$this->getNameField()] : '';
    }

    public function isNull()
    {
        return false;
    }

    public function getReadonlyForEdit()
    {
        return $this->isReadonlyForEdit;
    }

    public function customUpdate()
    {
        return false;
    }

    public function filter($type = null)
    {
        $this->filter = $type ?:$this->getClassNameString();

        if (!view()->exists('admin::list.filters.'.$this->filter)) {
            $this->filter = 'text';
        }

        return $this;
    }

    public function getFilterInput($list)
    {
        if ($this->filter) {
            $field = $this;
            $filterValue = $this->getFilter($list);
            $definition = $list->getDefinition();

            return view('admin::list.filters.' . $this->filter, compact('field', 'filterValue', 'definition'));
        }
    }

    public function default($value)
    {
        $this->defaultValue = $value;

        return $this;
    }

    public function placeholder(string $value)
    {
        $this->placeholderValue = $value;

        return $this;
    }

    public function getPlaceholder() : ?string
    {
        return $this->placeholderValue;
    }

    public static function make(...$arguments)
    {
        return new static(...$arguments);
    }

    public function sortable()
    {
        $this->isSortable = true;

        return $this;
    }

    public function isSortable()
    {
        return $this->isSortable;
    }

    public function onlyForm(bool $flag = true)
    {
        $this->onlyForm = $flag;

        return $this;
    }

    public function saveOnChange(bool $flag = true)
    {
        $this->isSaveOnChange = $flag;

        return $this;
    }

    public function isSaveOnChange()
    {
        return $this->isSaveOnChange;
    }

    public function fastEdit(bool $flag = true)
    {
        $this->fastEdit = $flag;

        return $this;
    }

    public function language()
    {
        $this->language = (new Language())->getLanguages();

        return $this;
    }

    public function getLanguage()
    {
        return $this->language;
    }

    public function getLanguageDefault()
    {
        return defaultLanguage();
    }

    public function rules($rules)
    {
        $this->rules = is_string($rules) ? (array)$rules : $rules;

        return $this;
    }

    public function getRules()
    {
        return $this->rules;
    }

    public function nullable($value)
    {
        $this->nullValue = $value;

        return $this;
    }

    public function isNullAble()
    {
        return (bool) $this->nullValue;
    }

    public function getNullValue()
    {
        return $this->nullValue;
    }

    public function isDisabled()
    {
        return false;
    }

    public function readonlyForEdit()
    {
        $this->isReadonlyForEdit = true;

        return $this;
    }

    public function comment(string $comment)
    {
        $this->commentText = $comment;

        return $this;
    }

    public function getComment() : string
    {
        return $this->commentText;
    }

    public function getFieldForm($definition)
    {
        $field = $this;
        $nameField = $this->getClassNameString();

        if ($this->getLanguage()) {
            $nameField .= '_lang';
        }

        return view('admin::form.fields.' . $nameField, compact('definition', 'field'))->render();
    }

    protected function getClassNameString() : string
    {
        return mb_strtolower(class_basename($this));
    }

    protected function convertQuery($query) : ?string
    {
        return mb_convert_case($query, MB_CASE_TITLE, "UTF-8");
    }

    public function isManyToMany()
    {
        return $this->isManyToMany;
    }

    public function hasOne($relation)
    {
        $this->relationHasOne = $relation;

        return $this;
    }

    public function getHasOne()
    {
        return $this->relationHasOne;
    }

    public function morphOne($relation)
    {
        $this->relationMorphOne = $relation;

        return $this;
    }

    public function getMorphOne()
    {
        return $this->relationMorphOne;
    }

    public function prepareSave(array $request)
    {
        $nameField = $this->getNameField();
    
        if (!array_key_exists($nameField, $request)) {
            return $this->isNullAble() ? $this->getNullValue() : '';
        }
    
        return $request[$nameField];
    }

    public function fastSave($definition, $request)
    {
        $model = $definition->model()->find($request['pk']);
        $model->{$request['ident']} = $request['value'];
        $model->save();

        $definition->clearCache();
    }

    public function hide($flag = true)
    {
        $this->isHide = $flag;

        return $this;
    }

    public function isHide()
    {
        return $this->isHide;
    }


}

