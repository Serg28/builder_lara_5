<?php

namespace Vis\Builder\Fields;

use Vis\Builder\Definitions\Resource;
use Vis\Builder\ManyToManySynced;

class ManyToManyAjax extends ManyToMany
{
    protected $sortableField;

    public function sortable(string $field = 'priority'): self
    {
        $this->sortableField = $field;

        return $this;
    }

    public function getSortableField(): ?string
    {
        return $this->sortableField;
    }

    public function search(Resource $definition) : array
    {
        return [
            'results' => $this->getOptions($definition),
        ];
    }

    public function getOptions(Resource $definition) : array
    {
        return $this->getDataWithWhereAndOrder($definition)->toArray();
    }

    public function getOptionsSelected(Resource $definition)
    {
        if (! request()->id) {
            return;
        }

        $relation = $definition->model()->{$this->options->getRelation()}();
        $table = $relation->getRelated()->getTable();
        $keyField = $this->options->getKeyField();

        $query = $definition->model()->find(request()->id)->{$this->options->getRelation()}()
            ->select(["{$table}.id", "{$table}.{$keyField} as name"]);

        if ($this->sortableField) {
            $pivotTable = $relation->getTable();
            $query->orderBy("{$pivotTable}.{$this->sortableField}");
        }

        $selected = $query->get(["{$table}.id", "{$table}.{$keyField} as name"])->toArray();

        return json_encode($selected);
    }

    public function save($collectionString, $model)
    {
        if (! $this->sortableField) {
            parent::save($collectionString, $model);

            return;
        }

        $collectionArray = array_values(array_filter(explode(',', $collectionString)));
        $relation = $this->options->getRelation();

        $model->{$relation}()->detach();

        if ($collectionArray) {
            $syncData = [];
            foreach ($collectionArray as $index => $id) {
                $syncData[$id] = [$this->sortableField => $index];
            }
            $model->{$relation}()->sync($syncData);
        }

        ManyToManySynced::dispatch(
            $model,
            $relation,
            collect($collectionArray)->filter()->toArray(),
        );
    }
}
