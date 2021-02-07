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

	public static function getCategories()
	{
		return [
			Movie::CATEGORY_RI      => '日剧',
			Movie::CATEGORY_MEI     => '美剧',
			Movie::CATEGORY_HAN     => '韩剧',
			Movie::CATEGORY_GANG    => '港剧',
			Movie::CATEGORY_TAI     => '泰剧',
			Movie::CATEGORY_YIN     => '印度剧',
			Movie::CATEGORY_BLGL    => '同性恋',
			Movie::CATEGORY_JIESHUO => '解说',
			Movie::CATEGORY_ZHONGGUO => '中国',
			Movie::CATEGORY_HOT => '热门/热播',
			Movie::CATEGORY_NEWST => '最新',
		];
	}
}
