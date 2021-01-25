<?php

namespace Haxibiao\Media\Traits;

use Haxibiao\Media\Movie;

trait MovieRepo
{
    public static function getCDNDomain($bucket)
    {
        return data_get(space_ucdn_map(), $bucket);
    }

    public function toResource()
    {
        $data = $this->toArray();
        unset($data['cover']);
        return array_merge($data, [
            'cover'  => $this->cover_url,
            'region' => $this->type_name_attr,
        ]);
    }

    public static function getStatus()
    {
        return [
            Movie::PUBLISH  => '可播放',
            Movie::DISABLED => '禁用',
            Movie::ERROR    => '资源错误',
        ];
    }
}
