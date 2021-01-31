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

    /**
     * 一对多的电影
     *
     * @return HasMany
     */
    public function movies(): HasMany
    {
        return $this->hasMany(Movie::class);
    }

    /**
     * 一对一的视频，比如视频动态Post
     *
     * @return void
     */
    public function video()
    {
        return $this->belongsTo('App\Video');
    }

    /**
     * 一对多的视频
     *
     * @return HasMany
     */
    public function videos(): HasMany
    {
        return $this->hasMany(Video::class);
    }
}
