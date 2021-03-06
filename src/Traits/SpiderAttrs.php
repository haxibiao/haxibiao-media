<?php

namespace Haxibiao\Media\Traits;

use Haxibiao\Breeze\Exceptions\GQLException;
use Haxibiao\Media\Spider;
use Illuminate\Support\Arr;

trait SpiderAttrs
{

    public function setSourceUrlAttribute($value)
    {
        throw_if(strlen($value) > 500, GQLException::class, '分享失败,Url不符合规定!');
        $this->attributes['source_url'] = $value;
    }

    public function getTitleAttribute()
    {
        //raw格式
        if ($shareTitle = data_get($this->raw, 'raw.item_list.0.share_info.share_title')) {
            return $shareTitle;
        }
        //精简提取过的格式
        if ($shareTitle = data_get($this->raw, 'title')) {
            return $shareTitle;
        }
        return Arr::get($this->data, 'title', '此人很懒什么也没说!');
    }

    /**
     * 秒粘贴秒播放地址
     */
    public function getPlayUrlAttribute()
    {
        //精简提取过的格式
        if ($play_url = data_get($this->raw, 'play_url')) {
            return $play_url;
        }
        //FIXME: 提供一个秒粘贴教程地址
        return Arr::get($this->data, 'play_url', env('PASTE_DEMO_URL'));
    }

    public function getRewardAttribute()
    {
        $reward = Spider::SPIDER_GOLD_REWARD;
        return Arr::get($this->data, 'reward', $reward);
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
