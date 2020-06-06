<?php

namespace haxibiao\media\Http\Api;

use haxibiao\media\Http\Controllers\Controller;
use haxibiao\media\Spider;
use Illuminate\Http\Request;
use Illuminate\Support\Arr;

class SpiderController extends Controller
{
    public function hook(Request $request)
    {
        $sourceUrl = $request->get('source_url');
        $data      = $request->get('data');
        if (!empty($sourceUrl)) {
            $spider = Spider::where('source_url', $sourceUrl)
                ->wating()
                ->first();
            $status = Arr::get($data, 'status');
            $video  = Arr::get($data, 'video');
            if (!is_null($spider)) {
                //重试n次仍然失败
                if ($status == 'INVALID_STATUS') {
                    $spider->status = Spider::INVALID_STATUS;
                    return $spider->delete();
                }

                //处理好的视频
                if (is_array($video)) {
                    return $spider->saveVideo($video);
                }
            }

        }

        dd('未处理成功');
    }
}
