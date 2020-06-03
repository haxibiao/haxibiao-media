<?php

namespace haxibiao\media\Traits;

use Illuminate\Database\Eloquent\Relations\MorphToMany;

trait Imageable
{

    public function imageable(): MorphToMany
    {
        return $this->morphToMany("\App\Imageable", 'imageable');
    }
}
