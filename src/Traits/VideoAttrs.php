<?php

namespace Haxibiao\Media\Traits;

use Illuminate\Support\Facades\Storage;
use Illuminate\Support\Str;

trait VideoAttrs
{
    public function getCountViewsAttribute()
    {
        $countViews = data_get($this, 'json.count_views', 0);
        return numberToReadable($countViews);
    }

    public function getDynamicCoverAttribute()
    {
        $dynamicCover = data_get($this, 'json.dynamic_cover');
        if (!$dynamicCover) {
            return data_get($this, 'json.cover');
        }
        return $dynamicCover;
    }

    public function getWidthAttribute()
    {
        return data_get($this, 'json.width', 576);
    }

    public function getHeightAttribute()
    {
        return data_get($this, 'json.height', 1024);
    }

    public function getCoversAttribute()
    {
        return $this->jsonData('covers');
    }

    public function getCoverUrlAttribute()
    {
        //前端需要null,不要空字符串
        if (empty($this->cover)) {
            return null;
        }
        $cover = $this->cover;

        //标准的vod cover url...
        if (Str::contains($cover, 'vod') && Str::contains($cover, 'http')) {
            return $cover;
        }

        //TODO: 修复数据，数据库统一存path
        $coverPath = parse_url($cover, PHP_URL_PATH);
        return cdnurl($coverPath);
    }

    public function getInfoAttribute()
    {
        $json = json_encode($this->json, true);

        // 相对路径 转 绝对路径
        $data = [
            'cover'  => Storage::cloud()->url($json['cover'] ?? '/images/cover.png'),
            'width'  => $json['width'] ?? null,
            'height' => $json['height'] ?? null,
        ];

        return $data;
    }

    public function getUrlAttribute()
    {
        if (Str::startsWith($this->path, 'http')) {
            return $this->path;
        }

        //还存本地...
        if (Storage::disk('public')->exists($this->path)) {
            return url('/storage/' . $this->path);
        }
        return Storage::cloud()->url($this->path);
    }

    /**
     * 获取视频地址
     */
    // public function getUrlAttribute(): string
    // {
    //     if ($this->isDameiVideo()) {
    //         $url = Storage::disk('public')->url($this->path);
    //     } else if ($this->isCosVideo()) {
    //         $json = $this->json;
    //         //存着转码高清视频
    //         $path = isset($json->transcode_hd_mp4) ? $json->transcode_hd_mp4 : $this->path;
    //         $url  = Storage::disk('cosv5')->url($path);
    //     } else if ($this->isDZVideo()) {
    //         $url = 'http://cosdtzq.haxibiao.com/' . $this->path;
    //     } else {
    //         //VOD视频流处理的视频
    //         $url = $this->path;
    //     }
    //     return $url;
    // }

    public function isDameiVideo()
    {
        return $this->disk == 'damei';
    }

    public function isCosVideo()
    {
        return $this->disk == 'cos';
    }

    public function isVodVideo()
    {
        return $this->disk == 'vod';
    }

    public function isDZVideo()
    {
        return $this->disk == 'dtzq';
    }

    /**
     * 获取视频时长
     */
    public function getDurationAttribute(): float
    {
        return $this->json->duration ?? 0;
    }

    /**
     * 获取视频封面
     */
    public function getCoverAttribute()
    {
        $cover = null;
        if (isset($this->json) && isset($this->json->cover)) {
            $cover = $this->json->cover;
        }
        return $cover;
    }

    /**
     * 获取视频信息
     */
    public function getVideoInfoAttribute()
    {
        return $this->json;
    }

    /**
     * 获取视频点赞总数
     */
    public function getCountLikesAttribute(): int
    {
        $count = 0;
        $type  = $this->type;
        if ($type == 'videos') {
            $count = $this->likes()->count();
        }
        return $count;
    }

    /**
     * 获取视频评论总数
     */
    public function getCountCommentsAttribute(): int
    {
        $count = 0;
        $type  = $this->type;
        if ($type == 'videos') {
            $count = $this->comments()->count();
        }
        return $count;
    }
}
