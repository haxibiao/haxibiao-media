<?php

namespace Haxibiao\Media\Traits;

use Haxibiao\Media\Novel;

trait NovelAttrs
{
    public function getScoreCache()
    {
        return '9.' . mt_rand(4, 9) . '分';
    }

    public function getDetailCache()
    {
        return '玄幻·种马·连载中';
    }

    public function getFriendNovelsCache()
    {
        return Novel::inRandomOrder()->take(6)->get();
    }

    public function getChapterCountCache()
    {
        return $this->chapters()->count();
    }
}
