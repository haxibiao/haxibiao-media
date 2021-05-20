<?php

namespace Haxibiao\Media\Traits;

trait SeriesAttrs
{
    public function getUrlAttribute()
    {
        if ($this->path) {
            $cdn = rand_pick_ucdn_domain();
            return "{$cdn}m3u8/{$this->bucket}/{$this->path}";
        }
        return $this->source;
    }

    public function getPlayLinesAttribute()
    {
        return [
            '默认线路' => $this->play_url,
            '线路二'  => $this->source,
        ];
    }
}
