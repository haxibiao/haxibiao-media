<?php

namespace Haxibiao\Media\Traits;

use Haxibiao\Media\Image;
use Illuminate\Database\Eloquent\Relations\MorphToMany;

trait Imageable
{

    /**
     * 关联的图片
     */
    public function images(): MorphToMany
    {
        return $this->morphToMany(Image::class, 'imageable');
    }

}
