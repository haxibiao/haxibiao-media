<?php

namespace Haxibiao\Media\Traits;

use Haxibiao\Helpers\utils\UcloudUtils;
use Haxibiao\Media\Series;
use Illuminate\Support\Str;

trait SeriesRepo
{
    public static function checkTs($id)
    {
        //修复当前已经上传到cdn m3u8文件是否可以正常播放
        //获取s3保存的m3u8文件地址
        $series = Series::find($id);
        //获取内容
        $content = file_get_contents(UcloudUtils::getCDNDomain($series->bucket) . $series->path);

        $list = collect(explode("\n", $content, -2));
        if ($list->count() < 30) {
            info("该m3u8文件 ts数量小于30 id:" . $series->id);
            echo "该m3u8文件 ts数量小于30 id:" . $series->id . "\n";
            return false; //连30条ts都没有 肯定坏了
        }
        $index = 0;
        $urls  = $list->random(30);
        foreach ($urls as $url) {

            if ($index == 3) {
                return true;
            }
            if (strpos($url, "ts")) {
                if (accessOK(UcloudUtils::getCDNDomain($series->bucket) . $url)) {
                    $index++;
                } else {
                    info("异常ts:" . UcloudUtils::getCDNDomain($series->bucket) . $url . '-' . "accessOK method 结果:" . accessOK(UcloudUtils::getCDNDomain($series->bucket) . $url));
                    return false;
                }
            }
            echo $url . "\n";
        }
    }

    //截取M3u8文件中 15 ~20 这个位置的ts视频中的第一秒
    public static function Screenshots($bucket, $m3u8Url)
    {
        $content = file_get_contents(UcloudUtils::getCDNDomain($bucket) . $m3u8Url);
        $list    = collect(explode("\n", $content))->slice(15, 20);
        foreach ($list as $url) {
            if (strpos($url, 'ts')) {
                $url         = UcloudUtils::getCDNDomain($bucket) . $url;
                $fileName    = Str::random(45) . '.jpg';
                $storagePath = storage_path('app/public/' . $fileName);

                exec(" ffmpeg -i {$url}  -ss 00:00:01 -f image2  -loglevel quiet -frames:v 1 {$storagePath}");
                return [$storagePath, $fileName];
            }
        }
    }
}
