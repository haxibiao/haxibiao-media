<?php

namespace Haxibiao\Media\Traits;

use Illuminate\Support\Facades\Storage;
use Illuminate\Support\Str;

/**
 * 部分地方的还在引用之前函数命名,所以下面保留了函数命名。后面可以慢慢替换
 */
trait ImageAttrs
{
    private static $small = 1; //小图
    private static $top   = 2; //小图

    //答题里版本
    public function getUrlAttribute()
    {
        //FIXME: fixdata期间有用
        if (strpos($this->path, 'http') !== false) {
            return $this->path;
        }

        if (empty($this->path)) {
            return url("images/notfound.png");
        }

        //默认没问题的图片都应该在cos
        return Storage::cloud()->url($this->path);
    }

    // FIXME:兼容Web端就兼容不了APP端 还是得改vue代码

    // public function getPathAttribute()
    // {
    //     //默认没问题的图片都应该在cos
    //     return Storage::cloud()->url($this->attributes['path']);
    // }

    public function getIsCosAttribute()
    {
        return $this->disk == "cos" || starts_with($this->path, 'http');
    }

    public function getThumbnailAttribute()
    {
        //TODO 重构任务
        if (Str::startsWith($this->path_small(), 'http')) {
            return $this->path_small();
        }
        return cdnurl($this->path_small());
    }

    /**
     * 获取当前环境的APP_URL
     * @return [type] [description]
     */
    public function webAddress()
    {
        switch (env('APP_ENV')) {
            case 'prod': //线上环境
                return env('APP_URL');
                break;
            case 'staging':
                return sprintf('http://staging.%s', env('APP_DOMAIN'));
                break;
            default:
                break;
        }
    }

    public function url()
    {
        return $this->path();
    }

    public function path_small()
    {
        return $this->path(self::$small);
    }

    public function path_top()
    {
        return $this->path(self::$top);
    }

    public function path($size = 0)
    {
        $path = $this->path;
        if (empty($path)) {
            return null;
        }
        $extension    = pathinfo($path, PATHINFO_EXTENSION);
        $folder       = pathinfo($path, PATHINFO_DIRNAME);
        $url_formater = $folder . '/' . basename($path, '.' . $extension) . '%s' . $extension;
        switch ($size) {
            case 1:
                return sprintf($url_formater, '.small.');
                break;
            case 2:
                return sprintf($url_formater, '.top.');
                break;
            default:
                return $path;
                break;
        }
    }
}
