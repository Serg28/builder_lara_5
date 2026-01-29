<?php

namespace Linecore\Cms\ControllersNew;

use Linecore\Cms\Services\Listing;

class ListController
{
    private $definition;

    public function __construct($definition)
    {
        $this->definition = $definition;
    }

    public function list()
    {
        $list = new Listing($this->definition);
        $listingRecords = $list->body();

        return view('admin::new.list.table', compact('list', 'listingRecords'));
    }
}
