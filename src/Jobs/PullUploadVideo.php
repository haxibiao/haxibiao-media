<?php

namespace Haxibiao\Media\Jobs;

use App\Post;
use App\Video;
use Haxibiao\Helpers\utils\VodUtils;
use Haxibiao\Media\Spider;
use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Foundation\Bus\Dispatchable;
use Illuminate\Queue\InteractsWithQueue;
use Illuminate\Support\Str;

class PullUploadVideo implements ShouldQueue
{
    use Dispatchable, InteractsWithQueue, Queueable;

    public $timeout = 300;

    protected $video, $post;

    /**
     * Create a new job instance.
     *
     * @return void
     */
    public function __construct($video, $post)
    {
        $this->video = $video;
        $this->post  = $post;
        $this->onQueue('douyin');
    }

    /**
     * Execute the job.
     *
     * @return void
     */
    public function handle()
    {
        $video = $this->video;
        try {

            //FIXME: 重构 使用vod拉取上传 到 哈希云
            $taskID = json_decode(VodUtils::PullUpload($video->url), true)['TaskId'];
            $fileID = null;
            $path   = null;
            do {
                sleep(10);
                $taskInfo = VodUtils::getTaskInfo($taskID);
                $status   = trim($taskInfo['status']);
                $fileID   = data_get($taskInfo, 'data.fileId');
                $path     = data_get($taskInfo, 'data.fileUrl');
            } while (Str::contains($status, 'PROCESSING'));
            if (!Str::contains($status, 'FINISH')) {
                throw new \Exception('处理异常');
            }
            VodUtils::makeCoverAndSnapshots($fileID);

            //哈希云处理成功的回调 web hook
            $videoInfo = null;
            $cover     = null;
            do {
                // 等待截图完成
                sleep(5);
                $videoInfo = VodUtils::getVideoInfo($fileID);
                $cover     = data_get($videoInfo, 'basicInfo.coverUrl');
            } while ($cover == null);
            $width    = data_get($videoInfo, 'metaData.width');
            $height   = data_get($videoInfo, 'metaData.height');
            $duration = data_get($videoInfo, 'metaData.duration');

            //通过video Observer维护 post 和 spider
            $video->update([
                'width'        => $width,
                'height'       => $height,
                'duration'     => $duration,
                'path'         => $path,
                'cover'        => $cover,
                'disk'         => 'vod',
                'qcvod_fileid' => $fileID,
                'status'       => Video::CDN_VIDEO_STATUS,
                'json'         => data_get($videoInfo, 'metaData'),
            ]);

            //cdn预热
            VodUtils::pushUrlCacheWithVODUrl($path);
            //更新spider 的任务状态
            if ($spider = Spider::where('spider_id', $video->id)->first()) {
                $spider->status = Spider::FAILED_STATUS;
                $spider->saveQuietly();
            }

        } catch (\Throwable $th) {

            //FIXME: 哈希云处理失败回调 web hook
            // 标记失败视频
            $this->video->update([
                'status' => Video::UNPROCESS_STATUS,
            ]);
            // 下架视频动态
            if ($post = $this->video->post) {
                $post->update([
                    'status' => Post::DELETED_STATUS,
                ]);
            }

            throw $th;
        }
    }
}
