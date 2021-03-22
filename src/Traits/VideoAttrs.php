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
        $cover_path = $this->cover;

        //前端需要null,不要空字符串
        if (empty($cover_path)) {
            return null;
        }

        //标准的 vod cover url, 抖音粘贴的爬虫处理结果...
        if (Str::contains($cover_path, 'http')) {
            return $cover_path;
        }

        //media中心自己上传的视频，封面同步到 hasvod 的
        // && Str::startsWith($cover_path, 'images/')
        if ('vod' == $this->disk) {
            return hash_vod_url($cover_path);
        }

        //最后检查本地或者默认cdn cos的文件，返回FULL URL
        $coverPath = parse_url($cover_path, PHP_URL_PATH);
        return cdnurl($coverPath);
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
        if (Str::startsWith($this->path, 'http')) {
            return $this->path;
        }

        if ('vod' == $this->disk) {
            $json = json_decode($this->json, true);
            return data_get($json, 'vod.MediaUrl');
        }

        if (!Storage::disk('public')->exists($this->path)) {
            //自己项目cos上?
            return cdnurl($this->path);
        }

        //还存本地?
        return url('/storage/' . $this->path);
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
     * 获取视频封面
     */
    public function getCoverAttribute()
    {
        $cover = $this->getRawOriginal('cover');
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
