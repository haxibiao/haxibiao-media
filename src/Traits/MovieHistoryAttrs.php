<?php

namespace Haxibiao\Media\Traits;

use Haxibiao\Media\Movie;

trait MovieHistoryAttrs
{
    /**
     * 已播放进度条数用整数
     *
     * @return void
     */
    public function getProgressAttribute()
    {
        return intval($this->getRawOriginal('progress') ?? 0);
    }

    public function getProgressMsgAttribute()
    {
        if ($this->progess) {
            return "{$this->series_name} {$this->progess}";
        } else {
            $movie = Movie::find($this->movie_id);
            return "{$movie->name} {$this->series_name}";
        }
    }
}
