<?php

namespace Haxibiao\Media\Http\Api;

use Haxibiao\Media\Http\Controller;
use Haxibiao\Media\Spider;
use Haxibiao\Media\Video;
use Illuminate\Http\Request;
use Illuminate\Support\Arr;

class HookController extends Controller
{
    /**
     * 兼容旧版spider的 media hook
     *
     * @param Request $request
     * @return void
     */
    public function hookSpider(Request $request)
    {

        $sourceUrl = $request->get('source_url');
        $data      = $request->get('data');

        $data = is_array($data) ? $data : json_decode($data, true);

        if ($spider = Spider::findByUrl($sourceUrl)) {
            $status   = Arr::get($data, 'status');
            $videoArr = Arr::get($data, 'video');

            // FIXME: 粘贴视频的可以解释出评论？
            // $comment  = data_get($data, 'raw.comment');

            if (!is_null(data_get($videoArr, 'cover'))) {
                // 有封面，就算处理好的视频
                if (is_array($videoArr)) {
                    $spider->hookVideo($videoArr);
                }
                $spider->status = Spider::PROCESSED_STATUS;
                $spider->saveQuietly();
                return ['status' => 'SUCCESS'];
            }
        }
        return [
            'status' => 'error',
            'reason' => 'hook spider not exist',
        ];
    }

    /**
     * 以后主要的video 粘贴和上传的 vod任务完成的 video/hook
     *
     * @param Request $request
     * @return void
     */
    public function hookVideo(Request $request)
    {
        $fileid = $request->get('fileid');
        $data   = $request->get('data');
        //返回的video json 含 vod结果信息在 json
        $videoArr = is_array($data) ? $data : json_decode($data, true);
        if ($video = Video::findByFileId($fileid)) {
            //处理好的视频
            $video->hook($videoArr);
            return [
                'status' => 'SUCCESS',
            ];
        }
        return [
            'status' => 'error',
            'reason' => 'hook video not exist',
        ];
    }
}
