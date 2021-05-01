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

    /**
     * 自己上传视频文件截图时存json信息（vod时代主要json是vod返回的videoinfo）
     */
    public function getCoversAttribute()
    {
        return $this->jsonData('covers');
    }

    /**
     * 获取视频封面
     */
    public function getCoverAttribute()
    {
        //已存好vod或者cdn的封面图片
        $cover = $this->getRawOriginal('cover');
        if (filter_var($cover, FILTER_VALIDATE_URL)) {
            return $cover;
        }
        //临时处理中的粘贴外部cdn/动态封面封面
        if (isset($this->json) && isset($this->json->cover)) {
            $cover = $this->json->cover ?? $this->json->dynamic_cover;
        }

        //media中心自己上传的视频，封面同步到 hasvod 的
        // && Str::startsWith($cover_path, 'images/')
        if ('vod' == $this->disk) {
            return hash_vod_url($cover);
        }

        //存留在cloud存储storage里的
        return cdnurl($cover);
    }

    public function getCoverUrlAttribute()
    {
        return $this->getCoverAttribute();
    }

    public function getInfoAttribute()
    {
        $json = json_encode($this->json, true);

        // 相对路径 转 绝对路径
        $data = [
            'cover'  => cdnurl($json['cover'] ?? '/images/cover.png'),
            'width'  => $json['width'] ?? null,
            'height' => $json['height'] ?? null,
        ];

        return $data;
    }

    public function getUrlAttribute()
    {
        // 云同步的cdn url
        if (Str::startsWith($this->path, 'http')) {
            return $this->path;
        }

        // VOD 方式上传的
        if ('vod' == $this->disk) {
            $json     = $this->json;
            $isString = is_string($json);
            if ($isString) {
                $json = json_decode($json, true);
            }
            $url = data_get($json, 'json.vod.MediaUrl');
            if (empty($url)) {
                $url = data_get($json, 'vod.MediaUrl');
            }
            //兼容抖音秒粘贴
            if (empty($url)) {
                $url = data_get($json, 'douyin.play_url');
            }

            return $url;
        }

        // 还在存本地的情况
        if (Storage::disk('public')->exists($this->path)) {
            return url($this->path);
        }

        // 默认云存储的cdn
        return cdnurl($this->path);
    }

    /**
     * @deprecated 命名不精准,建议使用 isStoredDamei() 进行替换
     * @see isStoredDamei()
     *
     * @return boolean
     */
    public function isDameiVideo()
    {
        return $this->isStoredDamei();
    }

    /**
     * @deprecated 命名不精准,建议使用 isStoredCos() 进行替换
     * @see isStoredCos()
     *
     * @return boolean
     */
    public function isCosVideo()
    {
        return $this->isStoredCos();
    }

    /**
     * @deprecated 命名不精准,建议使用 isStoredVod() 进行替换
     * @see isStoredVod()
     *
     * @return boolean
     */
    public function isVodVideo()
    {
        return $this->isStoredVod();
    }

    /**
     * @deprecated 命名不精准,建议使用 isStoredDZ() 进行替换
     * @see isStoredDZ()
     *
     * @return boolean
     */
    public function isDZVideo()
    {
        return $this->isStoredDZ();
    }

    public function isStoredDamei()
    {
        return 'damei' == $this->disk;
    }

    public function isStoredCos()
    {
        return 'cos' == $this->disk;
    }

    public function isStoredDZ()
    {
        return 'dtzq' == $this->disk;
    }

    public function isStoredVod()
    {
        return 'vod' == $this->disk;
    }

    /**
     * 获取视频时长
     */
    public function getDurationAttribute(): float
    {
        if ($duration = $this->getRawOriginal('duration') ?? null) {
            return $duration;
        }
        return $this->json->duration ?? 0;
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
        if ('videos' == $type) {
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
        if ('videos' == $type) {
            $count = $this->comments()->count();
        }
        return $count;
    }

    public function getIsHlsAttribute()
    {
        return str_contains($this->url, '.m3u8');
    }
}
