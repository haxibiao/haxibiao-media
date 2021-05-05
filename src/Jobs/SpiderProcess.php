<?php

namespace Haxibiao\Media\Jobs;

use GuzzleHttp\Client;
use Haxibiao\Media\Spider;
use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Foundation\Bus\Dispatchable;
use Illuminate\Queue\InteractsWithQueue;
use Illuminate\Queue\SerializesModels;
use Illuminate\Support\Arr;

/**
 * 原MediaProcess, 主要处理爬虫粘贴回调
 */
class SpiderProcess implements ShouldQueue
{
    use Dispatchable, InteractsWithQueue, Queueable, SerializesModels;

    protected $spider;

    /**
     * Create a new job instance.
     *
     * @return void
     */
    public function __construct($spider)
    {
        $this->spider = $spider;
        $url          = $this->spider->source_url;
        if (strpos($url, 'tiktok.com')) {
            $this->onQueue('tiktoks');
        } else {
            $this->onQueue('spiders');
        }
    }

    /**
     * Execute the job.
     *
     * @return void
     */
    public function handle()
    {
        $spider = $this->spider;
        if (!is_null($spider)) {
            // 爬虫粘贴回调
            $hookUrl = url('api/media/hook');
            $data    = [];
            $client  = new Client();

            //提交或者重试爬虫
            $api      = \Haxibiao\Media\Video::getMediaBaseUri() . 'api/spider/paste';
            $response = $client->request('GET', $api, [
                'http_errors' => false,
                'query'       => [
                    'source_url' => urlencode(trim($spider->source_url)),
                    'hook_url'   => $hookUrl,
                ],
            ]);
            $contents = $response->getBody()->getContents();
            if (!empty($contents)) {
                $contents   = json_decode($contents, true);
                $data       = Arr::get($contents, 'data');
                $status     = Arr::get($data, 'status');
                $shareTitle = data_get($data, 'raw.raw.item_list.0.share_info.share_title');

                // 404 not found video
                $isFailed = $status == 'INVALID_STATUS';
                if ($isFailed) {
                    return $spider->delete();
                }
            }

            //已经被处理过的，重试的话秒返回...
            $video = Arr::get($data, 'video');
            if (is_array($video) && $spider->isWating()) {
                $spider->hook($video);
            }

            // 修复乱码标题
            if ($spider->isDirty()) {
                $spider->saveQuietly();
            }
        }
    }
}
