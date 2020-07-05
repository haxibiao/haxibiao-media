<?php

namespace Haxibiao\Media\Traits;

use Haxibiao\Media\Image;
use Haxibiao\Media\Spider;
use Haxibiao\Media\Video;
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
