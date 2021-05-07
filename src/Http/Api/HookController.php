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
            $comment  = data_get($data, 'raw.comment');
            if (!is_null($spider)) {
                //重试n次仍然失败
                if ($status == 'INVALID_STATUS') {
                    $spider->status = Spider::INVALID_STATUS;
                    return $spider->save(); //不删除这个爬虫信息，保留！
                }

                $dataArr = $spider->data;
                // 粘贴视频的评论？
                $dataArr['comment'] = $comment;
                $spider->data       = $dataArr;
                $spider->save();

                //处理好的视频
                if (is_array($videoArr)) {
                    return $spider->hookVideo($videoArr);
                }
            }
        }
        return ['error' => 'hook spider failed'];
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
        $data   = $request->get('data'); //返回video json 和 vod json

        $data = is_array($data) ? $data : json_decode($data, true);

        if ($video = Video::findByFileId($fileid)) {
            //处理好的视频
            return $video->hook($data);
        }
        return ['error' => 'hook video failed'];
    }
}
