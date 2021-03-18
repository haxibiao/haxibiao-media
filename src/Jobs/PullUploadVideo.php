<?php

namespace Haxibiao\Media\Jobs;

use App\Post;
use App\Spider;
use App\Video;
use Haxibiao\Helpers\utils\VodUtils;
use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Foundation\Bus\Dispatchable;
use Illuminate\Queue\InteractsWithQueue;

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
            // 使用vod拉取上传
            $taskID = json_decode(VodUtils::PullUpload($video->url), true)['TaskId'];
            $fileID = null;
            $path   = null;
            do {
                sleep(10);
                $taskInfo = VodUtils::getTaskInfo($taskID);
                $status   = trim($taskInfo['status']);
                $fileID   = data_get($taskInfo, 'data.fileId');
                $path     = data_get($taskInfo, 'data.fileUrl');
            } while (\Str::contains($status, 'PROCESSING'));
            if (!\Str::contains($status, 'FINISH')) {
                throw new \Exception('处理异常');
            }
            VodUtils::makeCoverAndSnapshots($fileID);
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
            VodUtils::pushUrlCacheWithVODUrl($path);
            $this->post->update([
                'status' => Post::PUBLISH_STATUS,
            ]);
            Spider::where([
                'spider_id'   => $this->post->id,
                'spider_type' => 'posts',
            ])->update('status', Spider::PROCESSED_STATUS);
        } catch (\Throwable $th) {
            $this->post->update([
                'status' => Post::DELETED_STATUS,
            ]);
            $this->video->update([
                'status' => Video::UNPROCESS_STATUS,
            ]);
            throw $th;
        }
    }
}
