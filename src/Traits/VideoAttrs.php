<?php

namespace Haxibiao\Media\Traits;

use Haxibiao\Content\Post;
use Haxibiao\Media\Video;
use Illuminate\Support\Facades\Storage;

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
        $width  = data_get($this, 'json.width', 576);
        $height = data_get($this, 'json.height', 1024);
        $rotate = data_get($this, 'json.rotate', 0);
        return $rotate == 90 ? $height : $width;
    }

    public function getHeightAttribute()
    {
        $width  = data_get($this, 'json.width', 576);
        $height = data_get($this, 'json.height', 1024);
        $rotate = data_get($this, 'json.rotate', 0);
        return $rotate == 90 ? $width : $height;
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
        if ($this->post && $this->post->status == Post::PRIVARY_STATUS) {
            return Video::REVIEW_COVER;
        }
        //已存好vod或者cdn的封面图片
        $cover = $this->getRawOriginal('cover');
        if (filter_var($cover, FILTER_VALIDATE_URL)) {
            return $cover;
        }

        //cloud存储只留path  - 现在我们没有自己处理video，都vod，只有URL
        // if (!blank($cover)) {
        //     return cdnurl($cover);
        // }

        //临时处理中的粘贴外部cdn/动态封面封面
        if (isset($this->json) && isset($this->json->cover)) {
            $cover = $this->json->cover ?? $this->json->dynamic_cover;
            if (filter_var($cover, FILTER_VALIDATE_URL)) {
                return $cover;
            }
        }
        return null;
    }

    public function getCoverUrlAttribute()
    {
        return $this->getCoverAttribute();
    }

    public function getInfoAttribute()
    {
        //json里就是videoInfo 融合 fastVideoInfo 和vodVideoInfo 的属性
        return $this->json;
    }

    public function getUrlAttribute()
    {
        // path已处理为靠谱url
        if (filter_var($this->path, FILTER_VALIDATE_URL)) {
            return $this->path;
        }

        //依靠json对象解释播放地址
        $json = $this->json;

        // VOD 方式上传的
        if ('vod' == $this->disk) {
            //爱你城？格式不对...
            $url = data_get($json, 'json.vod.MediaUrl');
            if (empty($url)) {
                $url = data_get($json, 'vod.MediaUrl');
            }
            //兼容答赚视频json
            if (empty($url)) {
                $url = data_get($json, 'vod.sourceVideoUrl');
            }
            return $url;
        }

        //兼容秒粘贴乐观更新
        if (empty($url)) {
            $url = data_get($json, 'play_url');
            if (filter_var($url, FILTER_VALIDATE_URL)) {
                return $url;
            }
        }

        // 还在存本地的情况
        if (!blank($this->path) && Storage::disk('public')->exists($this->path)) {
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
            $count = $this->attributes['count_likes'] ?? $this->likes()->count();
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
            $count = $this->attributes['count_likes'] ?? $this->comments()->count();
        }
        return $count;
    }

    public function getIsHlsAttribute()
    {
        return str_contains($this->url, '.m3u8');
    }
}
