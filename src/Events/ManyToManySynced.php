<?php

namespace Vis\Builder;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Foundation\Events\Dispatchable;
use Illuminate\Queue\SerializesModels;

class ManyToManySynced
{
    use Dispatchable, SerializesModels;

    public function __construct(
        public Model  $model,
        public string $relation,
        public array  $syncedIds = [],
    )
    {
    }
}