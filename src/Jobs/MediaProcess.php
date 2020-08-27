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

//交给media服务处理视频
class MediaProcess implements ShouldQueue
{
    use Dispatchable, InteractsWithQueue, Queueable, SerializesModels;

    protected $spider;

    const API = 'http://media.haxibiao.com/api/v1/spider/store';

    /**
     * Create a new job instance.
     *
     * @return void
     */
    public function __construct($spiderId)
    {
        $this->spider = Spider::find($spiderId);
        $this->onQueue('spiders');
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
            //https有问题,会出现收不到post数据
            // $hookUrl  = 'http://hotfix.xiaodamei.com/api/media/hook';
            $hookUrl = config('media.hook');
            $data    = [];
            $client  = new Client();

            //提交或者重试爬虫
            $response = $client->request('GET', self::API, [
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
                $shareTitle = data_get($data, 'raw.raw.item_list.0.share_info.share_title');
                if (!empty($shareTitle)) {
                    $spider->setTitle($shareTitle);
                }

            }

            //已经被处理过的，重试的话秒返回...
            $video = Arr::get($data, 'video');
            if (is_array($video) && $spider->isWating()) {
                $spider->saveVideo($video);
            }

            // 修复乱码标题
            if ($spider->isDirty()) {
                $spider->save();
            }
        }
    }
}
