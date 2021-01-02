<?php

namespace Haxibiao\Media\Traits;

use Haxibiao\Helpers\utils\UcloudUtils;

trait SeriesAttrs
{
    public function getPlayUrlAttribute()
    {
        if ($this->path) {
            $cdn = UcloudUtils::getCDNDomain($this->bucket);
            return "{$cdn}{$this->path}";
        }
        return $this->source;
    }
}
