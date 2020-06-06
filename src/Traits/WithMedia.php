<?php

namespace haxibiao\media\Traits;

use haxibiao\media\Image;
use haxibiao\media\Spider;
use haxibiao\media\Video;
use Illuminate\Database\Eloquent\Relations\HasMany;

trait WithMedia
{
    public function spiders(): HasMany
    {
        return $this->hasMany(Spider::class);
    }

    public function images(): HasMany
    {
        return $this->hasMany(Image::class);
    }

    public function videos(): HasMany
    {
        return $this->hasMany(Video::class);
    }
}
