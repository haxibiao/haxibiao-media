<?php

namespace Haxibiao\Media\Traits;

use App\Exceptions\UserException;
use Haxibiao\Helpers\utils\QcloudUtils;
use Haxibiao\Media\Image;
use Illuminate\Support\Facades\Storage;
use Intervention\Image\Facades\Image as ImageMaker;
use Throwable;

trait ImageRepo
{

    //repo
    //TODO: 待重构这里
    public function fillForJs()
    {
        $this->url       = $this->getUrlAttribute();
        $this->path      = $this->getUrlAttribute();
        $this->url_small = $this->thumbnail;
    }

    /**
     * 保存base64的source为图片 - 目前GQL主要用的是这个
     */
    public static function saveImage($source)
    {
        ini_set('memory_limit', -1); //上传时允许最大内存

        if ($base64 = matchBase64($source)) {
            $source = $base64;
        }

        $imageMaker = ImageMaker::make($source);
        $mime       = explode('/', $imageMaker->mime());
        $extension  = end($mime);
        if (empty($extension)) {
            throw new UserException('上传失败');
        }
        //随机文件名
        $imageName = uniqid();
        //非prod环境也上传cos，避开文件名
        if (!is_prod_env()) {
            $imageName = $imageName . "." . env('APP_ENV');
        }
        //保存原始图片
        if (in_array(env("APP_NAME"), ["datizhuanqian", "damei"])) {
            $imageMaker = Image::autoCut($source); //兼容答妹，图片都自动裁剪最宽640...
        } else {
            $imageMaker = ImageMaker::make($source);
        }

        if ($extension != 'gif') {
            $big_width = $imageMaker->width() > 900 ? 900 : $imageMaker->width();
            $imageMaker->resize($big_width, null, function ($constraint) {
                $constraint->aspectRatio();
            });
        }
        $imageMaker->encode($extension, 100);

        Storage::cloud()->put('images/' . $imageName . '.' . $extension, $imageMaker->__toString());

        //保存缩略图
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
        Storage::cloud()->put('images/' . $imageName . '.small.' . $extension, $thumbnail->__toString());

        //使用原图hash
        $hash = hash_file('md5', cdnurl('images/' . $imageName . '.' . $extension));

        //hash值匹配直接返回当前image对象
        $image = self::firstOrNew([
            'hash' => $hash,
        ]);
        if (!empty($image->id)) {
            return $image;
        }

        //写入一条新记录
        $image->extension = $extension;
        $image->user_id   = getUserId();
        $image->width     = $imageMaker->width();
        $image->height    = $imageMaker->height();
        $image->path      = 'images/' . $imageName . '.' . $extension;
        $image->disk      = config('filesystems.cloud');
        $image->save();

        return $image;
    }

    /**
     * 保存UploadFile图片到云
     */
    public function save_file($file)
    {
        ini_set("memory_limit", -1); //为上传文件处理截图临时允许大内存使用
        //如果当前不是线上的环境则上传到本地
        if (!is_prod()) {
            return $this->save_file_to_local($file);
        }
        $extension       = $file->getClientOriginalExtension();
        $this->extension = $extension;
        $this->hash      = md5_file($file->path()) ?: null;
        $this->title     = $file->getClientOriginalName();
        $filename        = $this->id . '.' . $extension;
        try {
            $img = ImageMaker::make($file->path());

            //保存原始图片(宽度被处理:最大900)
            if ($extension != 'gif') {
                $big_width = $img->width() > 900 ? 900 : $img->width();
                $img->resize($big_width, null, function ($constraint) {
                    $constraint->aspectRatio();
                });
                $tmp_path = '/tmp/' . $filename; //将处理后的文件保存在系统的临时文件夹(该文件夹下的文件会定期清除)
                $img->save($tmp_path);
                $cos_file_info = QcloudUtils::uploadFile($tmp_path, $filename);
                if (empty($cos_file_info) || $cos_file_info['code'] != 0) {
                    throw new \Exception('上传到COS失败');
                }
            } else {
                $cos_file_info = QcloudUtils::uploadFile($file->path(), $filename);
                if (empty($cos_file_info) || $cos_file_info['code'] != 0) {
                    throw new \Exception('上传到COS失败');
                }
            }
            $cdn_url      = $cos_file_info['data']['custom_url'];
            $this->path   = parse_url($cdn_url, PHP_URL_PATH); //数据库存path
            $this->width  = $img->width();
            $this->height = $img->height();

            //保存轮播图
            if ($img->width() >= 760) {
                if ($extension != 'gif') {
                    $img->crop(760, 327);
                    $top_filename = $this->id . '.top.' . $extension;
                    $tmp_path     = '/tmp/' . $top_filename;
                    $img->save($tmp_path);
                    $cos_file_info = QcloudUtils::uploadFile($tmp_path, $top_filename);
                    if (empty($cos_file_info) || $cos_file_info['code'] != 0) {
                        throw new \Exception('上传到COS失败');
                    }
                    $this->path_top = $cos_file_info['data']['custom_url'];
                    //git图片存储逻辑后面需要调整
                } else {
                    $this->path_top = $this->path;
                }
            }
            //保存缩略图
            if ($img->width() / $img->height() < 1.5) {
                $img->resize(300, null, function ($constraint) {
                    $constraint->aspectRatio();
                });
            } else {
                $img->resize(null, 240, function ($constraint) {
                    $constraint->aspectRatio();
                });
            }
            $img->crop(300, 240);
            $small_filename = $this->id . '.small.' . $extension;

            $tmp_path = '/tmp/' . $small_filename;
            $img->save($tmp_path);

            $cos_file_info = QcloudUtils::uploadFile($tmp_path, $small_filename);
            //上传到COS失败
            if (empty($cos_file_info) || $cos_file_info['code'] != 0) {
                throw new \Exception('上传到COS失败');
            }

            $this->disk = config("app.name");
            $this->save();
        } catch (\Throwable $ex) {
            return $file->path();
        }
        return null;
    }

    /**
     * @deprecated  之前处理UploadFile图片上传到本地
     */
    private function save_file_to_local($file)
    {

        $extension       = $file->getClientOriginalExtension();
        $this->extension = $extension;
        $this->hash      = md5_file($file->getRealPath()) ?: null;
        $this->title     = $file->getClientOriginalName();
        $filename        = $this->id . '.' . $extension;
        $local_path      = '/storage/img/' . $filename;
        $this->path      = $this->webAddress() . $local_path;
        $local_dir       = public_path('/storage/img/');
        if (!is_dir($local_dir)) {
            mkdir($local_dir, 0777, 1);
        }

        try {
            $img = ImageMaker::make($file->getRealPath());
        } catch (Throwable $ex) {
            return $file->getRealPath() . ' === ' . $ex->getMessage();
        }
        if ($extension != 'gif') {
            $big_width = $img->width() > 900 ? 900 : $img->width();
            $img->resize($big_width, null, function ($constraint) {
                $constraint->aspectRatio();
            });
            //save big
            $img->save(public_path($local_path));
        } else {
            $file->move($local_dir, $filename);
        }
        $this->width  = $img->width();
        $this->height = $img->height();
        //save top
        if ($extension != 'gif') {
            if ($img->width() >= 760) {
                $img->crop(760, 327);
                $path_top       = '/storage/img/' . $this->id . '.top.' . $extension;
                $this->path_top = $this->webAddress() . $path_top;
                $img->save(public_path($path_top));
            }
        } else {
            if ($img->width() >= 760) {
                $this->path_top = $this->path;
            }
        }
        //save small
        if ($img->width() / $img->height() < 1.5) {
            $img->resize(300, null, function ($constraint) {
                $constraint->aspectRatio();
            });
        } else {
            $img->resize(null, 240, function ($constraint) {
                $constraint->aspectRatio();
            });
        }
        $img->crop(300, 240);
        $this->disk = "local";
        $path_mall  = '/storage/img/' . $this->id . '.small.' . $extension;
        $img->save(public_path($path_mall));
        $this->save();
        return null;
    }

    /**
     * 爬取image_url，保存图片到本地
     */
    public function save_image($image_url, $clientName = null)
    {
        ini_set("memory_limit", -1); //为上传文件处理截图临时允许大内存使用
        try {
            $image = ImageMaker::make($image_url);

            //获取image extension
            $image_mime_arr  = explode("/", $image->mime());
            $extension       = end($image_mime_arr);
            $this->extension = $extension;
            $this->title     = $clientName;
            $filename        = $this->id . '.' . $extension;

            //保存原始图片(宽度被处理:最大900)
            if ($extension != 'gif') {
                $big_width = $image->width() > 900 ? 900 : $image->width();
                $image->resize($big_width, null, function ($constraint) {
                    $constraint->aspectRatio();
                });
                $tmp_path = '/tmp/' . $filename; //将处理后的文件保存在系统的临时文件夹(该文件夹下的文件会定期清除)
                $image->save($tmp_path);
                $cos_file_info = QcloudUtils::uploadFile($tmp_path, $filename);
                if (empty($cos_file_info) || $cos_file_info['code'] != 0) {
                    throw new \Exception('上传到COS失败');
                }
                $this->hash = md5_file($tmp_path) ?: null;
            } else {
                $cos_file_info = QcloudUtils::uploadFile($file->path(), $filename);
                if (empty($cos_file_info) || $cos_file_info['code'] != 0) {
                    throw new \Exception('上传到COS失败');
                }
            }

            $this->path   = $cos_file_info['data']['custom_url']; //custom_url为CDN的地址
            $this->width  = $image->width();
            $this->height = $image->height();

            //保存轮播图
            if ($image->width() >= 760) {
                if ($extension != 'gif') {
                    $image->crop(760, 327);
                    $top_filename = $this->id . '.top.' . $extension;
                    $tmp_path     = '/tmp/' . $top_filename;
                    $image->save($tmp_path);
                    $cos_file_info = QcloudUtils::uploadFile($tmp_path, $top_filename);
                    if (empty($cos_file_info) || $cos_file_info['code'] != 0) {
                        throw new \Exception('上传到COS失败');
                    }
                    $this->path_top = $cos_file_info['data']['custom_url'];
                    //git图片存储逻辑后面需要调整
                } else {
                    $this->path_top = $this->path;
                }
            }
            //保存缩略图
            if ($image->width() / $image->height() < 1.5) {
                $image->resize(300, null, function ($constraint) {
                    $constraint->aspectRatio();
                });
            } else {
                $image->resize(null, 240, function ($constraint) {
                    $constraint->aspectRatio();
                });
            }
            $image->crop(300, 240);
            $small_filename = $this->id . '.small.' . $extension;

            $tmp_path = '/tmp/' . $small_filename;
            $image->save($tmp_path);

            $cos_file_info = QcloudUtils::uploadFile($tmp_path, $small_filename);
            //上传到COS失败
            if (empty($cos_file_info) || $cos_file_info['code'] != 0) {
                throw new \Exception('上传到COS失败');
            }

            $this->disk = config("app.name");
            $this->save();
        } catch (\Exception $e) {
            return $e->getMessage();
        }

        return $this->path;
    }

    //答妹的上传base64代码，多了个自动裁剪...

    // public static function saveImage($source)
    // {
    //     ini_set('memory_limit', -1); //上传时允许最大内存

    //     if ($base64 = self::matachBase64($source)) {
    //         $source = $base64;
    //     }

    //     $imageMaker = Image::autoCut($source);
    //     $mime       = explode('/', $imageMaker->mime());
    //     $extension  = end($mime);

    //     if (empty($extension)) {
    //         throw new UserException('上传失败');
    //     }
    //     //先生成一个随机文件名
    //     $imageName = uniqid() . ".{$extension}";

    //     //储存到本地
    //     if (!Storage::exists('images')) {
    //         Storage::makeDirectory('images');
    //     }
    //     $savePath = storage_path('app/public/images/') . $imageName;

    //     //保存图片
    //     $imageMaker->save($savePath);
    //     $hash = hash_file('md5', $savePath);
    //     //hash值匹配直接返回当前image对象
    //     $image = self::firstOrNew([
    //         'hash' => $hash,
    //     ]);

    //     //相同图片秒返回,为了测试后面cos，不秒返回
    //     if (!is_testing_env()) {
    //         if (!empty($image->id)) {
    //             return $image;
    //         }
    //     }

    //     //写入一条新记录
    //     $image->extension = $extension;
    //     $image->count     = 0;
    //     $image->user_id   = getUserId();
    //     $image->width     = $imageMaker->width();
    //     $image->height    = $imageMaker->height();
    //     $image->save();
    //     //{id}.damei.jpg
    //     $imageName = sprintf('%d.%s.%s', $image->id, config('app.name'), $extension);
    //     //非prod环境也上传cos，避开文件名
    //     if (!is_prod_env()) {
    //         $imageName = $image->id . "." . env('APP_ENV') . ".{$extension}";
    //     }
    //     $image->path = 'images/' . $imageName;
    //     $image->save();

    //     //上传到Cos，统一用Storage::cloud()
    //     $cosPath     = 'storage/app/images/' . $imageName;
    //     $cosUploaded = Storage::cloud()->put($cosPath, file_get_contents($savePath));

    //     //上传成功 更新头像路径
    //     if ($cosUploaded) {
    //         $image->path = $cosPath;
    //         if (is_testing_env()) {
    //             \info("cos 图片上传成功:" . $image->path);
    //         }
    //         $image->disk = 'cos';
    //     } else {
    //         $image->disk = \gethostname(); //方便日后从web服务修复图片数据
    //     }
    //     $image->save();
    //     return $image;
    // }

    public function saveDownloadImage($file)
    {
        if ($file) {
            $task_logo = 'images/images' . $this->id . '_' . time() . '.png';
            $cosDisk   = \Storage::cloud();
            $cosDisk->put($task_logo, \file_get_contents($file->path()));

            return $task_logo;
        }
    }

    //自动裁剪为最宽640
    public static function autoCut($source, $maxWidth = 640)
    {
        $image = ImageMaker::make($source);
        if ($maxWidth == null) {
            return $image;
        }

        if ($image->width() > $maxWidth) {
            //自动裁剪
            $image->fit($maxWidth);
        }
        return $image;
    }
}
