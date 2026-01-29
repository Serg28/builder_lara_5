<?php

namespace App\Cms\Definitions;

use Linecore\Cms\Services\Actions;
use Linecore\Cms\Models\Language;
use Linecore\Cms\Fields\{Checkbox, Select};
use Linecore\Cms\Definitions\Resource;

class Languages extends Resource
{
    public $model = Language::class;
    public $title = 'Языки сайта';
    protected $orderBy = 'priority asc';
    protected $isSortable = true;

    public function fields(): array
    {
        return [
            Select::make('Язык', 'language')
                ->options($this->model()->supportedLocales()),
            Checkbox::make('Активен', 'is_active')->fastEdit(),
        ];
    }

    public function actions(): Actions
    {
        return Actions::make()->insert()->hideActions();
    }

}
