<?php

namespace Haxibiao\Media\Http\Api;

use Haxibiao\Media\Http\Controller;
use Haxibiao\Media\Image;
use Illuminate\Http\Request;

class ImageController extends Controller
{
    public function index(Request $request)
    {
        $qb = Image::orderBy('id', 'desc');
        if (request('q')) {
            $qb = $qb->where('title', 'like', '%' . request('q') . '%');
        }
        $images = $qb->paginate(24);
        foreach ($images as $image) {
            $image->fillForJs();
        }
        return $images;
    }

    public function store(Request $request)
    {
        $user        = $request->user();
        $image_files = $request->photo;

        //向前兼容，有些页面还是使用单图上传逻辑
        if (!is_array($image_files)) {
            if ($request->photo) {
                $mimeType          = $request->photo->getClientMimeType();
                $original_filename = $request->original_filename;
                if (!str_contains($mimeType, 'image') && !str_contains($original_filename, 'image')) {
                    return "只支持添加图片文件";
                }
                $image          = new Image();
                $image->user_id = $user->id;
                $image->save();
                $image = $image->saveImage($request->photo);

                //给simditor编辑器返回上传图片结果...
                if ($request->get('from') == 'simditor') {
                    // $json = "{ success: true, msg:'图片上传成功', file_path: '" . $path_big . "' }";
                    $json            = (object) [];
                    $json->success   = true;
                    $json->msg       = '图片上传成功';
                    $json->file_path = $image->url;
                    return json_encode($json);
                }
                if ($request->from == 'post') {
                    $image->url = $image->url;
                    return $image;
                }
                return request('feedback') ? $image : $image->url;

            }
            return "没有发现上传的图片photo";
        }
        if (count($image_files) == 0) {
            return "没有发现上传的图片photo";
        }
        $result = [];
        foreach ($image_files as $image_file) {
            $extension = $image_file->getClientOriginalExtension();
            if (!in_array($extension, ['jpg', 'png', 'gif'])) {
                $result[] = "图片格式只支持jpg, png, gif";
            }
            $image = new Image();
            //eg.反馈不要求用户登录
            if (!empty($user)) {
                $image->user_id = $user->id;
            }
            $image->save();
            $image    = $image->saveImage($request->photo);
            $result[] = request('feedback') ? $image : $image->url;
        }
        return $result;

    }

    public function upload(Request $request)
    {
        $image       = new Image();
        $image_files = $request->picfile;

        // 兼容单图上传模式
        if (!is_array($image_files)) {
            $image = $image->saveImage($image_files);

            // 给simditor编辑器返回上传图片结果...
            if ($request->get('from') == 'simditor') {
                $json            = (object) [];
                $json->success   = true;
                $json->msg       = '图片上传成功';
                $json->file_path = $image->url;
                return json_encode($json);
            }

            return $image->url;
        }

        // 判断多图上传
        if (count($image_files) > 0) {
            // dd($image_files);

            $result = [];
            foreach ($image_files as $image_file) {
                $extension = $image_file->getClientOriginalExtension();
                if (!in_array($extension, ['jpg', 'png', 'gif'])) {
                    $result[] = "图片格式只支持jpg, png, gif";
                }
                $image    = new Image();
                $image    = $image->saveImage($image_file);
                $result[] = $image->url;
            }
            return $result;
        }

        return "未发现图片文件！";
    }
}
