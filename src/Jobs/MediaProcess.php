<?php

namespace haxibiao\media\Jobs;

use GuzzleHttp\Client;
use haxibiao\media\Spider;
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
        $this->spider = Spider::wating()->find($spiderId);
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
            $hookUrl  = 'http://hotfix.xiaodamei.com/api/media/hook';
            $data     = [];
            $client   = new Client();
            $response = $client->request('GET', self::API, [
                'http_errors' => false,
                'query'       => [
                    'source_url' => trim($spider->source_url),
                    'hook_url'   => $hookUrl,
                ],
            ]);
            $contents = $response->getBody()->getContents();
            if (!empty($contents)) {
                $contents = json_decode($contents, true);
                $data     = Arr::get($contents, 'data');
            }

            //已经被处理过
            $video = Arr::get($data, 'video');
            if (is_array($video)) {
                $spider->saveVideo($video);
            }
        }
    }
}
