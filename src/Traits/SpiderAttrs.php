<?php

namespace Haxibiao\Media\Traits;

use Haxibiao\Media\Spider;
use Illuminate\Support\Arr;

trait SpiderAttrs
{

    public function setSourceUrlAttribute($value)
    {
        throw_if(strlen($value) > 255, DataLengthException::class, '分享失败,Url不符合规定!');
        $this->attributes['source_url'] = $value;
    }

    public function getSourceUrlAttribute($value)
    {
        return trim($value);
    }

    public function getTitleAttribute()
    {
        return Arr::get($this->data, 'title', '此人很懒什么也没说!');
    }

    public function getRewardAttribute()
    {
        return Arr::get($this->data, 'reward', Spider::SPIDER_GOLD_REWARD);
    }

    public function getRemarkAttribute()
    {
        $status = $this->status;
        if ($status == Spider::WATING_STATUS) {
            $msg = '待处理';
        } else if ($status == Spider::PROCESSED_STATUS) {
            $msg = '发布成功';
        } else {
            $msg = '失败';
        }

        return $msg;
    }

    public function isProcessed()
    {
        return $this->status == Spider::PROCESSED_STATUS;
    }

    public function isWating()
    {
        return $this->status == Spider::WATING_STATUS;
    }

    public function isFailed()
    {
        return $this->status == Spider::FAILED_STATUS;
    }

}
