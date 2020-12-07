<?php

namespace Haxibiao\Media\Traits;

trait MovieRepo
{
    public static function getCDNDomain($bucket)
    {
        return data_get([
            'hanju'      => 'https://cdn-youku-com.diudie.com/',
            'riju'       => 'https://cdn-xigua-com.diudie.com/',
            'meiju'      => 'https://cdn-iqiyi-com.diudie.com/',
            'gangju'     => 'https://cdn-v-qq-com.diudie.com/',
            'blgl'       => 'https://cdn-pptv-com.diudie.com/',
            // 印剧数量少，使用 do spaces cdn domain
            'yinju'      => 'https://yinju.sfo2.cdn.digitaloceanspaces.com/',
            'othermovie' => 'https://cdn-leshi-com.diudie.com/',
            'movieimage' => 'https://cdn-douyin-com.diudie.com/',
        ], $bucket);
    }
}
