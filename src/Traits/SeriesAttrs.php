<?php

namespace Haxibiao\Media\Traits;

trait SeriesAttrs
{
    public function getPlayUrlAttribute()
    {
        if ($this->path) {
            $cdn = rand_pick_ucdn_domain();
            return "{$cdn}m3u8/{$this->bucket}/{$this->path}";
        }
        return $this->source;
    }
}
