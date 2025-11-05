<?php

namespace Vis\Builder\Fields;

use Illuminate\Support\Str;
use Illuminate\Database\Eloquent\Relations\Relation;

class Definition extends Field
{
    protected $definitionRelation;
    protected $relation;
    protected $onlyForm = true;
    protected $typeRelative;
    protected $hasActions = true;
    protected $cachedRelationDefinitions = [];

    public function hasMany($relation, $classDefinitionRelation = null)
    {
        $this->relation = $relation;
        $this->definitionRelation = $classDefinitionRelation;
        $this->typeRelative = 'hasMany';
        return $this;
    }

    public function morphMany($relation, $classDefinitionRelation = null)
    {
        $this->relation = $relation;
        $this->definitionRelation = $classDefinitionRelation;
        $this->typeRelative = 'morphMany';
        return $this;
    }

    public function hasActions(bool $value = true)
    {
        $this->hasActions = $value;
        return $this;
    }

    public function getHasActions()
    {
        return $this->hasActions;
    }

    /**
     * Безопасно получить связанный Definition.
     * Возвращает null, если связи нет или класс не существует.
     */
    public function getDefinitionRelation($definition)
    {
        // Кэшируем результат на время запроса
        $cacheKey = spl_object_hash($definition);
        if (isset($this->cachedRelationDefinitions[$cacheKey])) {
            return $this->cachedRelationDefinitions[$cacheKey];
        }

        $model = $definition->model();

        // если метод связи не существует — возвращаем null
        if (!$this->relation || !method_exists($model, $this->relation)) {
            return $this->cachedRelationDefinitions[$cacheKey] = null;
        }

        $relation = $model->{$this->relation}();

        // если не объект Relation — возвращаем null
        if (!$relation instanceof Relation) {
            return $this->cachedRelationDefinitions[$cacheKey] = null;
        }

        // если явно указан класс Definition — используем его
        if ($this->definitionRelation && class_exists($this->definitionRelation)) {
            return $this->cachedRelationDefinitions[$cacheKey] = new $this->definitionRelation();
        }

        // пытаемся определить Definition по имени связанной модели
        $related = $relation->getRelated();
        $fullPathClass = 'App\\Cms\\Definitions\\' . Str::plural(class_basename($related));

        if (!class_exists($fullPathClass)) {
            return $this->cachedRelationDefinitions[$cacheKey] = null;
        }

        return $this->cachedRelationDefinitions[$cacheKey] = new $fullPathClass();
    }

    public function getAttributes($definition)
    {
        $definitionRelation = $this->getDefinitionRelation($definition);

        // если связи нет — ничего не возвращаем
        if (!$definitionRelation) {
            return '';
        }

        $attributes = [
            'name' => $this->getNameField(),
            'table' => $definitionRelation->model()->getTable(),
            'caption' => $definitionRelation->model()->getTable(),
            'definition' => $definitionRelation->getNameDefinition(),
            'definition_parent' => $definition->getNameDefinition(),
            'ident' => $this->getNameField(),
            'foreign_field' => $this->getFieldForeignKeyName($definition),
            'path_definition' => addslashes($this->definitionRelation),
            'model_parent' => addslashes($definition->getFullPathDefinition()),
            'type_relation' => $this->typeRelative,
        ];

        if ($definitionRelation->getIsSortable()) {
            $attributes['sortable'] = 'priority';
        }

        if ($this->typeRelative === 'morphMany') {
            $relation = $definition->model()->{$this->relation}();

            if (method_exists($relation, 'getMorphType')) {
                $attributes['morph_type'] = $relation->getMorphType();
                $attributes['model_base'] = addslashes($definition->model);
            }
        }

        return json_encode($attributes);
    }

    private function getFieldForeignKeyName($definition)
    {
        $model = $definition->model();

        if (!$this->relation || !method_exists($model, $this->relation)) {
            return '';
        }

        $relation = $model->{$this->relation}();

        return method_exists($relation, 'getForeignKeyName')
            ? $relation->getForeignKeyName()
            : '';
    }

    public function getTable($definition, $parseJsonData)
    {
        $definitionRelation = $this->getDefinitionRelation($definition);

        // если связи нет — просто возвращаем пустой HTML
        if (!$definitionRelation) {
            return ['html' => '', 'count_records' => 0];
        }

        $attributes = json_encode($parseJsonData);
        $perPage = $definitionRelation->getPerPage();

        if (request('count')) {
            session()->put($definitionRelation->getSessionKeyPerPage(), ['per_page' => request('count')]);
        }

        $count = $definitionRelation->getPerPageThis();
        $model = $definition->model();
        $listModel = request('id') ? $model::find(request('id')) : (new $model());
        $list = $listModel->{$this->relation}()->paginate($count);
        $list->appends(['count' => $count]);

        $fieldsDefinition = $this->head($definition);

        $list->map(function ($item) use ($fieldsDefinition, $definition) {
            $item->fields = clone $fieldsDefinition;
            $fieldsDefinition->map(function ($item2, $key) use ($item, $definition) {
                $item->fields[$key] = clone $item2;
                $item2->setValue($item);
                $item->fields[$key]->value = $item2->getValueForList($definition);
            });
        });

        $urlAction = 'actions/' . $definition->getNameDefinition();
        $isSortable = $definitionRelation->getIsSortable();
        $hasActions = $this->getHasActions();

        return [
            'html' => view('admin::form.fields.partials.input_definition_table_data', compact(
                'definitionRelation',
                'fieldsDefinition',
                'list',
                'attributes',
                'urlAction',
                'isSortable',
                'perPage',
                'count',
                'hasActions'
            ))->render(),
            'count_records' => 0,
        ];
    }

    public function remove($definition, $parseJsonData)
    {
        $definitionRelation = $this->getDefinitionRelation($definition);

        if (!$definitionRelation) {
            return ['html' => '', 'count_records' => 0];
        }

        $definitionRelation->model()->destroy(request('idDelete'));
        $definitionRelation->clearCache();

        return $this->getTable($definition, $parseJsonData);
    }

    protected function head($definition)
    {
        $definitionRelation = $this->getDefinitionRelation($definition);

        if (!$definitionRelation) {
            return collect();
        }

        $fields = $definitionRelation->getAllFields();

        return collect($fields)->reject(fn($name) => $name->isOnlyForm());
    }

    public function getNameField(): string
    {
        return Str::slug(parent::getNameField(), '_');
    }

    /**
     * Проверяет, есть ли валидная связь.
     */
    public function hasValidRelation($definition): bool
    {
        return (bool) $this->getDefinitionRelation($definition);
    }
}