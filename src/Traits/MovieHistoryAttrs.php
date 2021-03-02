<?php

namespace Haxibiao\Media\Traits;

use Haxibiao\Media\Movie;

trait MovieHistoryAttrs
{
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
