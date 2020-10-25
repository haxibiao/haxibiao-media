<?php

namespace Haxibiao\Media\Http\Api;

use Haxibiao\Media\Http\Controllers\Controller;
use Haxibiao\Media\Spider;
use Illuminate\Http\Request;
use Illuminate\Support\Arr;

class SpiderController extends Controller
{
    public function hook(Request $request)
    {
        $sourceUrl  = $request->get('source_url');
        $data       = $request->get('data');
        $data       = is_array($data) ? $data : json_decode($data, true);
        $shareTitle = data_get($data, 'raw.raw.item_list.0.share_info.share_title');

        if (!empty($sourceUrl)) {
            $spider = Spider::where('source_url', $sourceUrl)->first();
            $status = Arr::get($data, 'status');
            $video  = Arr::get($data, 'video');

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

                $dataFromModel        = $spider->data;
                $dataFromModel['raw'] = data_get($data, 'raw.raw', []);
                $spider->data         = $dataFromModel;
                $spider->save();

                //处理好的视频
                if (is_array($video)) {
                    return $spider->saveVideo($video);
                }
            }

            // 修复乱码标题
            if ($spider->isDirty()) {
                $spider->save();
            }

        }

        dd('未处理成功');
    }
}
