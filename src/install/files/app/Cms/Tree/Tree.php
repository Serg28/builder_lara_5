<?php

namespace App\Cms\Tree;

use App\Cms\Tree\Templates\Contacts;
use App\Cms\Tree\Templates\Node;
use Linecore\Cms\Definitions\BaseTree;

class Tree extends BaseTree
{
    public function templates()
    {
        return [
            'main' => Node::class,
            'contacts' => Contacts::class
        ];
    }
}
