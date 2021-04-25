<?php

namespace Haxibiao\Media\Traits;

use Haxibiao\Media\Image;
use Illuminate\Http\UploadedFile;
use Illuminate\Support\Facades\Storage;
use Intervention\Image\Facades\Image as ImageMaker;
use Throwable;

trait ImageRepo
{

    public function fillForJs()
    {
        $this->url       = $this->getUrlAttribute();
        $this->path      = $this->getUrlAttribute();
        $this->url_small = $this->thumbnail;
    }

    /**
     * 保存base64|path|url的图片
     * @param string  $source
     * @return Image
     */
    public static function saveImage($source)
    {
        if ($base64 = matchBase64($source)) {
            $source = $base64;
        }
        $imageMaker = ImageMaker::make($source);
        $mime       = explode('/', $imageMaker->mime());
        $extension  = end($mime) ?? 'png';

        // 随机文件名
        $imageName = uniqid();
        // 不同环境测试，区别文件名后缀
        if (!is_prod_env()) {
            $imageName = $imageName . "." . env('APP_ENV');
        }
        $filename = $imageName . '.' . $extension;

        // 原图临时文件
        $tmp_path = '/tmp/' . $filename;
        $imageMaker->save($tmp_path);

        // 计算原图hash
        $hash = md5_file($tmp_path);

        $cloud_path = 'storage/app-' . env('APP_NAME') . '/images/' . $filename;

        // 部分项目环境支持自动裁剪,有些简单原始图片保存,故此通过开关控制
        $auto_cut = config('media.image.auto_cut');

        // 保留原始图片(尺寸最大保留900)
        if ($auto_cut) {
            if ($extension != 'gif') {
                $big_width = $imageMaker->width() > 900 ? 900 : $imageMaker->width();
                $imageMaker->resize($big_width, null, function ($constraint) {
                    $constraint->aspectRatio();
                });
            }
            $imageMaker->encode($extension, 100);
        }

        // 上传原图
        if ($extension == 'gif') {
            Storage::put($cloud_path, @file_get_contents($source));
        } else {
            Storage::cloud()->put($cloud_path, $imageMaker->__toString());
        }

        // 保存缩略图(尺寸最大保留300)
        if ($auto_cut) {
            $thumbnail = ImageMaker::make($source);
            if ($thumbnail->width() / $thumbnail->height() < 1.5) {
                $thumbnail->resize(300, null, function ($constraint) {
                    $constraint->aspectRatio();
                });
            } else {
                $thumbnail->resize(null, 240, function ($constraint) {
                    $constraint->aspectRatio();
                });
            }
            $thumbnail->crop(300, 240);
            $thumbnail->encode($extension, 100);
            $cloud_path_small = str_replace($extension, 'small.' . $extension, $cloud_path);
            Storage::cloud()->put($cloud_path_small, $thumbnail->__toString());
        }

        // 排重图片
        $image = self::firstOrNew([
            'hash' => $hash,
        ]);
        if (!empty($image->id)) {
            return $image;
        }

        // 保存图片记录
        $image->extension = $extension;
        $image->user_id   = getUserId();
        $image->width     = $imageMaker->width();
        $image->height    = $imageMaker->height();
        $image->path      = $cloud_path;
        $image->disk      = config('filesystems.cloud');
        $image->save();

        return $image;
    }

    /**
     * 保存UploadedFile图片到云 - 网站后端编辑用
     * @param UploadedFile $file
     * @return string 图片的url
     */
    public function saveImageFile(UploadedFile $file)
    {
        try {
            $image        = static::saveImage($file->path());
            $image->title = request('title') ?? $file->getClientOriginalName(); //支持上传自定义图片标题
            $img          = ImageMaker::make($file->path());

            $extension = $file->getClientOriginalExtension();
            //尺寸够宽，自动保存轮播图用的图
            if ($img->width() >= 760) {
                if ($extension != 'gif') {
                    $img->crop(760, 327);
                    $top_filename = $this->id . '.top.' . $extension;
                    $tmp_path     = '/tmp/' . $top_filename;
                    $img->save($tmp_path);
                    $cloud_path = 'storage/app-' . env('APP_NAME') . '/images/' . $top_filename;
                    Storage::cloud()->put($cloud_path, file_get_contents($tmp_path));
                    $image->path_top = $this->path;
                }
            }
            $image->save();
        } catch (\Throwable $ex) {

        }
        return null;
    }

    /**
     * 爬取image_url，保存图片到本地
     */
    public function saveRemoteImage($image_url, $title = null)
    {
        $image        = static::saveImage($image_url);
        $image->title = $title ?? request('title');
        $image->save();
        return $image->url;
    }

    public function saveDownloadImage($file)
    {
        if ($file) {
            $task_logo = 'storage/app-' . env('APP_NAME') . '/images/' . $this->id . '_' . time() . '.png';
            Storage::cloud()->put($task_logo, file_get_contents($file->path()));
            return $task_logo;
        }
    }
}
