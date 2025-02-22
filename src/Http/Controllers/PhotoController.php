<?php

namespace Vis\Builder;

use Illuminate\Routing\Controller;

class PhotoController extends Controller
{
    private $definition;

    public function __construct()
    {
        $pathDefinition = request('path_model') ?? null;

        if($pathDefinition) {
            $this->definition = new $pathDefinition();
        }
        return null;
    }

    public function upload()
    {
        return $this->getThisField()?->upload($this->definition);
    }

    public function selectPhotos()
    {
        return $this->getThisField()?->selectWithUploadedImages($this->definition);
    }

    private function getThisField()
    {
        return $this->definition?->getAllFields()[request('ident')] ?? null;
    }
}

