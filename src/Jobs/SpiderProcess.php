<?php

namespace haxibiao\media\Jobs;

use App\Gold;
use haxibiao\media\Spider;
use haxibiao\media\UploadVideo;
use haxibiao\media\Video;
use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Foundation\Bus\Dispatchable;
use Illuminate\Queue\InteractsWithQueue;
use Illuminate\Support\Arr;
use Illuminate\Support\Facades\Storage;

class SpiderProcess implements ShouldQueue
{
    use Dispatchable, InteractsWithQueue, Queueable;

    protected $spider;

    /**
     * Create a new job instance.
     *
     * @return void
     */
    public function __construct($spiderId)
    {
        $this->spider = Spider::wating()->find($spiderId);
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
            //处理视频上传

            $spiderServer = $this->getSpiderServer($spider->source_url);
            try {
                $contens = file_get_contents($spiderServer);
                $contens = json_decode($contens, true);
            } catch (\Exception $ex) {
                $spider->status = Spider::FAILED_STATUS;
                $spider->save();
                return;
            }

            //json响应格式
            if (is_array($contens)) {
                $data = $contens['data'];
                if ($contens['code'] == 200) {
                    $videoUrl = $data['video'];
                    $raw      = $data['raw'];

                    //更新spider
                    $spider->raw    = $raw;
                    $spider->status = Spider::FAILED_STATUS;

                    //下载视频到本地磁盘
                    $publicStorage = Storage::disk('public');
                    $videoName     = basename($videoUrl);
                    $videoPath     = sprintf('videos/%s', $videoName);
                    $saveSuccess   = $publicStorage->put($videoPath, file_get_contents($videoUrl));
                    if ($saveSuccess) {
                        $videoDiskPath = $publicStorage->path($videoPath);

                        //计算hash,数据不存在,`写入DB
                        $hash  = hash_file('md5', $videoDiskPath);
                        $video = Video::firstOrNew(['hash' => $hash]);
                        if (!isset($video->id)) {
                            $video->fill([
                                'user_id'  => $spider->user_id,
                                'path'     => $videoPath,
                                'disk'     => 'damei',
                                'filename' => $videoName,
                                'type'     => 'videos',
                            ])->save();

                            $spiderdata = $spider->data;
                            $goldReward = Spider::SPIDER_GOLD_REWARD;

                            //更新spider data
                            $spiderdata['video_id'] = $video->id;
                            $spiderdata['reward']   = $goldReward;
                            $spider->data           = $spiderdata;
                            $spider->status         = Spider::PROCESSED_STATUS;
                            $spider->spider_type    = 'videos';
                            $spider->spider_id      = $video->id;

                            //队列去处理视频上传
                            dispatch(new UploadVideo($video->id))->onQueue('videos');

                            $user = $spider->user;
                            //触发奖励
                            Gold::makeIncome($user, $goldReward, '分享视频奖励');
                            //扣除精力
                            if ($user->ticket > 0) {
                                $user->decrement('ticket');
                            }
                        } else {
                            $spider->data = ['job_failed' => ['msg' => '该视频已存在!', 'video_id' => $video->id]];
                        }
                    }

                    $spider->save();
                }
            }
        }
    }

    public function getSpiderServer($url)
    {
        $servers = [
            'gz01.haxibiao.com',
            'gz02.haxibiao.com',
            'gz03.haxibiao.com',
            'gz04.haxibiao.com',
            'gz05.haxibiao.com',
            'gz06.haxibiao.com',
            'gz07.haxibiao.com',
            'gz08.haxibiao.com',
        ];
        $spiderServer = sprintf('http://%s', Arr::random($servers) . '/simple-spider/index.php?url=' . $url);

        return $spiderServer;
    }
}
