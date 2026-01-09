<?php

namespace Vis\Builder;

use Carbon\Carbon;
use Illuminate\Routing\Controller;
use Illuminate\Support\Str;
use Vis\Builder\Services\Listing;

class ExportController extends Controller
{
    public function download($definition)
    {
        $modelDefinition = $this->getModelDefinition($definition);
        $modelExport = request('model');


        $listing = new Listing(new $modelDefinition());

        if (!class_exists($modelExport)) {
            $modelExport = str(request('model'))->ltrim('\\')->prepend('\\')->toString();
            $modelExport = collect(explode('\\', $modelExport))
                ->map(fn($part) => Str::studly($part))
                ->implode('\\');
        }

        return (new $modelExport($listing))->download($definition . '_' . Carbon::now() . '.xlsx');

    }

    private function getModelDefinition($definition)
    {
        return "App\\Cms\\Definitions\\" . ucfirst(Str::camel($definition));
    }
}