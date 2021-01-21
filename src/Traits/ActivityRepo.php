<?php

namespace Haxibiao\Media\Traits;

use Haxibiao\Media\Activity;
use Illuminate\Support\Facades\Storage;

trait ActivityRepo
{
    /**
     * 通过 type 获取活动轮播图
     *
     * @param $type 活动轮播图类型
     */
    public static function activities(int $type)
    {
        return Activity::query()
            ->whereType($type)
            ->enable()
            ->orderByDesc('sort');
    }
    public function saveDownloadImage($file)
    {
        if ($file) {
            $cover   = '/movies' . $this->id . '_' . time() . '.png';
            $cosDisk = Storage::cloud();
            $cosDisk->put($cover, \file_get_contents($file->path()));

            return cdnurl($cover);
        }
    }
}
