<?php

namespace Haxibiao\Media\Traits;

use App\SeekMovie;
use Haxibiao\Media\Spider;
use Haxibiao\Media\Video;
use Illuminate\Database\Eloquent\Relations\HasMany;

/**
 * 用户的 media 特性
 */
trait UseMedia
{
    public function spiders(): HasMany
    {
        return $this->hasMany(Spider::class);
    }

    public function videos(): HasMany
    {
        return $this->hasMany(Video::class);
    }

    public function movies(): HasMany
    {
        return $this->hasMany(Movie::class);
    }

    public function seekMovies()
    {
        return $this->hasMany(SeekMovie::class);
    }
}
