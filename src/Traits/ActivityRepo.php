<?php

namespace Haxibiao\Media\Traits;

use Haxibiao\Media\Activity;
use Illuminate\Http\UploadedFile;

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

    /**
     * 保存活动轮播图
     */
    public function saveActivityImage(UploadedFile $file)
    {
        //置顶用的封面文件名
        $filename = sprintf("%s.%s", $this->id . "_top_movie_" . time(), 'png');
        $folder   = storage_folder('activities');
        $file->storeAs($folder, $filename);
        $cloud_path = sprintf("%s/%s", $folder, $filename);
        $cdnurl     = cdnurl($cloud_path);
        info('nova 上传图片。。' . $cdnurl);
        return $cdnurl;
    }
}
