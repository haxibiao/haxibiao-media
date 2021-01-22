<?php

namespace Haxibiao\Media\Traits;

use Haxibiao\Media\Movie;
use Haxibiao\Media\Video;
use Illuminate\Database\Eloquent\Relations\HasMany;

/**
 * 内容的 media 特性
 */
trait WithMedia
{
    use WithImage;

    public function movies(): HasMany
    {
        return $this->hasMany(Movie::class);
    }

    public function videos(): HasMany
    {
        return $this->hasMany(Video::class);
    }
}
