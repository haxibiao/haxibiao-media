<?php

namespace Haxibiao\Media\Traits;

use Haxibiao\Media\Image;
use Haxibiao\Media\Spider;
use Haxibiao\Media\Video;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Database\Eloquent\Relations\MorphToMany;

trait WithImage
{
    public function images(): MorphToMany
    {
        return $this->morphToMany(Image::class, 'imageable','imageable')
            ->withTimestamps();
    }
}
