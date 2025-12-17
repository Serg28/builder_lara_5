<?php

namespace Vis\Builder\ControllersNew;

use Illuminate\Http\Request;

class SlugifyController
{

    public function create(Request $request) {
        $text = $request->string('text')->toString();
        $handler = $request->string('handler')->toString();

        $slug = \Str::slug($text); // fallback

        if ($handler && class_exists($handler)) {
            $object = $handler::make('test');

            if (method_exists($object, 'makeSlug')) {
                $slug = $object->makeSlug($text);
            }
        }

        return response()->json([
            'slug' => $slug,
        ]);
    }
}