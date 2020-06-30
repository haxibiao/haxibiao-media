<?php

namespace haxibiao\media\Http\Api;

use haxibiao\helpers\VodUtils;
use haxibiao\media\Http\Controllers\Controller;
use haxibiao\media\Video;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Log;

class VideoController extends Controller
{

    protected $responseData = ['status' => 'success', 'code' => 200, 'message' => ''];

    public function store(Request $request)
    {
        //前端vod上传成功后保存视频信息
        if (!empty($request->fileId)) {
            $video = Video::firstOrNew([
                'fileid' => $request->fileId,
            ]);
            $video->user_id  = getUserId();
            $video->path     = $request->videoUrl;
            $video->filename = $request->videoName;
            $video->disk     = 'vod';
            $video->save();

            //调用vod处理视频封面的任务
            VodUtils::makeCover($request->fileId);
            return $video;
        }

        return '没有腾讯云视频id';
    }

    public function importVideo(Request $request)
    {
        if ($data = $request->get('data')) {
            $video = Video::firstOrNew([
                'path' => $data['path'],
            ]);
            try {
                $video->fill($data);
                $video->save();
                return 1;
            } catch (\Exception $ex) {
                return -1;
            }
        }
        abort(404);
    }

    public function uploadVideo(Request $request)
    {
        $root = $request->root();
        if ($video = $request->file('video')) {

            //正式环境 禁止通过upload || video 二级域名传上来的视频.
            if (is_prod_env() && !preg_match('#//(upload|video).*?#', $root)) {
                abort(500, "上传失败!");
            }
            if ($video->extension() != 'mp4') {
                abort(500, '视频格式不正确,请上传正确的MP4视频!');
            }

            $video = Video::saveVideoFile($video, $request->input(), $request->user());

            return $video;
        }
        abort(500, "没上传视频文件过来");
    }

    //这个XXM视图用cos处理视频时测试用
    public function cosHookVideo(Request $request)
    {
        $inputs = $request->input();

        $playUrl  = array_get($inputs, 'playurl');
        $cosAppId = env('COS_APP_ID');
        $bucket   = env('COS_BUCKET');

        Log::channel('cos_video_hook')->info($inputs);

        //COS配置
        if (!is_null($cosAppId) && !is_null($bucket) && !is_null($playUrl)) {
            //获得文件名称
            $bucketPrefix = sprintf('/%s/%s/', $cosAppId, $bucket);
            $fileSuffix   = '.f30.mp4';
            $videoPath    = str_replace([$bucketPrefix, $fileSuffix], '', $playUrl);

            //获取视频
            $video = Video::where('path', $videoPath)->first();
            if (!is_null($video)) {
                $json                   = $video->json;
                $json->transcode_hd_mp4 = str_replace($bucketPrefix, '', $playUrl);
                $video->json            = $json;
                $video->syncStatus();
                $video->save();

                return [$json];
            }
        }
    }
}
