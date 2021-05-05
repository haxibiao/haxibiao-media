<?php

namespace Haxibiao\Media\Http\Api;

use Haxibiao\Media\Http\Controller;
use Haxibiao\Media\Spider;
use Haxibiao\Media\Video;
use Illuminate\Http\Request;
use Illuminate\Support\Arr;

class HookController extends Controller
{
    public function hookSpider(Request $request)
    {
        $sourceUrl  = $request->get('source_url');
        $data       = $request->get('data');
        $data       = is_array($data) ? $data : json_decode($data, true);
        $shareTitle = data_get($data, 'raw.raw.item_list.0.share_info.share_title');

        if ($spider = Spider::findByUrl($sourceUrl)) {
            $status   = Arr::get($data, 'status');
            $videoArr = Arr::get($data, 'video');
            $comment  = data_get($data, 'raw.comment');
            if (!is_null($spider) && !$spider->isProcessed()) {

                //重新获取json中的标题
                if (!empty($shareTitle)) {
                    $spider->setTitle($shareTitle);
                }

                //重试n次仍然失败
                if ($status == 'INVALID_STATUS') {
                    $spider->status = Spider::INVALID_STATUS;
                    return $spider->save(); //不删除这个爬虫信息，保留！
                }

                $dataFromModel            = $spider->data;
                $dataFromModel['raw']     = data_get($data, 'raw.raw', []);
                $dataFromModel['comment'] = $comment;
                $spider->data             = $dataFromModel;
                $spider->save();

                //处理好的视频
                if (is_array($videoArr)) {
                    return $spider->hook($videoArr);
                }
            }

            // // 修复乱码标题
            // if ($spider && $spider->isDirty()) {
            //     $spider->save();
            // }

        }
        return ['error' => 'hook spider failed'];
    }

    public function hookVideo(Request $request)
    {
        $fileid = $request->get('fileid');
        $data   = $request->get('data'); //返回video json 和 vod json
        $data   = is_array($data) ? $data : json_decode($data, true);

        if ($video = Video::findByFileId($fileid)) {
            //处理好的视频
            return $video->hook($data);
        }
        return ['error' => 'hook video failed'];
    }
}
