<?php

namespace Haxibiao\Media\Jobs;

use GuzzleHttp\Client;
use Haxibiao\Media\Video;
use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Foundation\Bus\Dispatchable;
use Illuminate\Queue\InteractsWithQueue;
use Illuminate\Support\Arr;

class VodProcess implements ShouldQueue
{
    use Dispatchable, InteractsWithQueue, Queueable;

    protected $video;

    /**
     * Create a new job instance.
     *
     * @return void
     */
    public function __construct(Video $video)
    {
        $this->video = $video;
    }

    /**
     * Execute the job.
     *
     * @return void
     */
    public function handle()
    {
        $video = $this->video;
        if (blank($video->fileid)) {
            return;
        }

        //处理 video 的 vod 信息和封面，并hook回来
        $hookUrl = url('api/video/hook');
        $data    = [];
        $client  = new Client();

        //提交 syncvod
        $api      = \Haxibiao\Media\Video::getMediaBaseUri() . 'api/video/paste';
        $response = $client->request('GET', $api, [
            'http_errors' => false,
            'query'       => [
                'fileid'     => urlencode(trim($video->fileid)),
                'share_link' => urlencode(trim($video->sharelink)),
                'hook_url'   => $hookUrl,
            ],
        ]);
        $contents = $response->getBody()->getContents();

        if (!empty($contents)) {
            $contents = json_decode($contents, true);
            $data     = Arr::get($contents, 'data');
            $status   = Arr::get($data, 'status');

            // vod如果不存在fileid
            $isFailed = $status < 1;
            if ($isFailed) {
                //标记删除
                return $video->delete();
            }
        }
    }
}
